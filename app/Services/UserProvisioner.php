<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * إنشاء صف `users` لحساب جديد — بديل trigger `handle_new_user` على `auth.users`
 * (SPEC 1.4 + الإصلاح الأمني 1.9.16 + مهمة ClickUp #9).
 *
 * الـ trigger القديم كان:
 * 1. يقبل أي قيمة من الـ metadata مع fallback صامت (`user_type` → engineer،
 *    `status` → pending، و⚠ `selected_plan: professional` → `free`).
 * 2. يبتلع كل الأخطاء بـ `EXCEPTION WHEN OTHERS → RAISE WARNING` فيخرج
 *    مستخدم auth **بدون** صف في `public.users` بصمت.
 *
 * هنا المنطق داخل register (يوم 4) وبأقوى من الـ trigger:
 * - whitelist صريح لقيم `user_type` — قيمة خارج القائمة = خطأ، لا fallback صامت.
 * - `status = pending` و`current_step = 1` و`selected_plan = free` **server-side
 *   فقط** (1.9.3: المستخدم مايتحكمش في حالته/خطوته/باقته من الـ body).
 * - slug = من الإيميل + لاحقة تفرد `-1, -2, ...` (نفس منطق الـ trigger بس بلا
 *   اعتماد على metadata) ومقاومة لـ race condition على الفهرس الفريد.
 * - أي خطأ ينطبق لأعلى (لا صمت) — الـ register يرد برسالة صريحة (1.9.16).
 */
final class UserProvisioner
{
    /** الأسماء المعتمدة للتسجيل — `admin` يُنشأ منSeeder/الإدارة فقط. */
    private const ALLOWED_TYPES = [
        AccountType::Customer,
        AccountType::Engineer,
        AccountType::Office,
        AccountType::Exhibition,
    ];

    /** محاولات توليد slug عند تصادم متزامن على الفهرس الفريد. */
    private const SLUG_ATTEMPTS = 3;

    /**
     * إنشاء الحساب الجديد.
     *
     * @param  array{
     *     email: string,
     *     user_type: string,
     *     name?: string|null,
     *     phone?: string|null,
     *     password?: string|null,
     *     slug?: string|null,
     * }  $attributes
     *
     * @throws InvalidArgumentException برسالة عربية when البيانات خارج الـ whitelist
     */
    public function provision(array $attributes): User
    {
        $lookup = new UserLookupService;
        $email = $lookup->normalize((string) ($attributes['email'] ?? ''));

        if ($email === '' || ! preg_match(EMAIL_REGEX, $email)) {
            throw new InvalidArgumentException('البريد الإلكتروني غير صالح');
        }

        if ($lookup->exists($email)) {
            throw new InvalidArgumentException('هذا البريد الإلكتروني مسجل بالفعل');
        }

        $userType = strtolower(trim((string) ($attributes['user_type'] ?? '')));
        $allowed = array_map(static fn (AccountType $type): string => $type->value, self::ALLOWED_TYPES);

        if (! in_array($userType, $allowed, true)) {
            throw new InvalidArgumentException('نوع الحساب غير صالح');
        }

        $name = trim((string) ($attributes['name'] ?? '')) ?: 'مستخدم';
        $phone = $this->normalizePhone($attributes['phone'] ?? null);

        // slug: المُرسل صراحة (لو فيه) وإلا من الإيميل — ثم لاحقة التفرد (-1, -2...)
        // قبل الكتابة (نفس تسلسل الـ trigger)، والـ retry في insertUser يغطي التزامن.
        $requestedSlug = trim((string) ($attributes['slug'] ?? ''));
        $slug = Slug::unique(
            $requestedSlug !== '' ? Slug::make($requestedSlug) : Slug::fromEmail($email),
            static fn (string $candidate): bool => User::query()->where('slug', $candidate)->exists()
        );

        return $this->insertUser([
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'user_type' => $userType,
            'slug' => $slug,
            'password' => $attributes['password'] ?? null,
        ]);
    }

    /** تطبيع + تحقق من التليفون المصري (SPEC 7.1#14) — الفارغ يفضل فارغاً. */
    private function normalizePhone(mixed $phone): ?string
    {
        $phone = normalizePhone((string) ($phone ?? ''));

        if ($phone === '') {
            return null;
        }

        if (! isValidEgyptianPhone($phone)) {
            throw new InvalidArgumentException('رقم الهاتف غير صالح');
        }

        return $phone;
    }

    /**
     * الكتابة داخل transaction + إعادة المحاولة عند تصادم slug متزامن
     * (عوضاً عن `EXCEPTION WHEN OTHERS` الصامت في الـ trigger).
     *
     * @param  array<string, mixed>  $data
     */
    private function insertUser(array $data): User
    {
        $slug = $data['slug'];

        for ($attempt = 1; $attempt <= self::SLUG_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(static fn (): User => User::create([
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'user_type' => $data['user_type'],
                    'slug' => $slug,
                    'password' => $data['password'],
                    // server-side فقط (1.9.3).
                    'status' => 'pending',
                    'current_step' => 1,
                    'selected_plan' => 'free',
                ]));
            } catch (QueryException $e) {
                $duplicateSlug = str_contains(strtolower($e->getMessage()), 'slug');

                if (! $duplicateSlug || $attempt === self::SLUG_ATTEMPTS) {
                    throw $e;
                }

                // slug اتحجز في اللحظة نفسها → لاحقة تفرد جديدة (-1, -2, ...).
                $slug = Slug::unique($slug, static fn (string $candidate): bool => User::query()
                    ->where('slug', $candidate)
                    ->exists());
            }
        }

        throw new InvalidArgumentException('تعذّر إنشاء الحساب، حاول مرة أخرى');
    }
}
