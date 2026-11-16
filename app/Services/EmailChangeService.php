<?php

namespace App\Services;

use App\Models\User;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * تغيير بريد حساب غير مؤكد — بديل RPC `update_unconfirmed_user_email`
 * (SPEC 1.4 + الإصلاح الأمني 1.9.5 + 7.3#2 + مهمة ClickUp #9).
 *
 * ⛔ الثغرة القديمة: الـ RPC كان `SECURITY DEFINER` مع `GRANT EXECUTE TO anon`
 * ومسار Next.js بدون أي مصادقة — أي حد يعرف بريد حساب لسه ما اتأكدش يقدر
 * يغيّره لبرده (account takeover/squatting).
 *
 * القواعد هنا (نفس تسلسل الـ SQL بس):
 * 1. **مصادقة إلزامية**: يُشغَّل من endpoint محمي بـ Bearer — والخدمة ترفض
 *    أي حساب `email_verified_at` ممتلئ (الـ RPC كان يرجع "سجّل دخول لتغيير البريد").
 * 2. تطبيع (trim/lower) + رفض الفارغ/المتطابق.
 * 3. رفض البريد المأخوذ مسبقاً (unique).
 * 4. slug يُعاد توليده من البريد الجديد (أو من المُمرَّر) مع ضمان التفرد —
 *    الروابط القديمة تتعمل لها redirect في الفرونت.
 * 5. تحديث داخل transaction + إلغاء الجلسات القديمة (الجلسة مرتبطة بالبريد).
 *
 * الرد المتوقع لـ endpoint يوم 4 (`PATCH /api/v1/auth/email`):
 * `{success, new_email, new_slug, email_sent, message}`.
 */
final class EmailChangeService
{
    /**
     * تنفيذ التغيير.
     *
     * @param  string|null  $requestedSlug  slug مطلوب صراحةً (اختياري) — يُنظَّف ثم
     *                                      يُضبط بالتفرد، وإلا اشتقاق من البريد الجديد.
     * @return array{success: bool, new_email: string, new_slug: string}
     *
     * @throws InvalidArgumentException برسالة عربية
     */
    public function change(User $user, string $newEmail, ?string $requestedSlug = null): array
    {
        $lookup = new UserLookupService;
        $newEmail = $lookup->normalize($newEmail);

        // (1) الحساب لسه غير مؤكد — وإلا التغيير من إعدادات الحساب بعد الدخول.
        if ($user->getAttribute('email_verified_at') !== null) {
            throw new InvalidArgumentException('تم تأكيد بريدك الإلكتروني مسبقاً، سجّل الدخول لتغييره');
        }

        // (2) رفض الفارغ أو المتطابق.
        if ($newEmail === '' || ! preg_match(EMAIL_REGEX, $newEmail)) {
            throw new InvalidArgumentException('البريد الإلكتروني غير صالح');
        }

        if ($newEmail === $lookup->normalize((string) $user->getAttribute('email'))) {
            throw new InvalidArgumentException('البريد الإلكتروني الجديد يجب أن يختلف عن الحالي');
        }

        // (3) رفض البريد المسجَّل لحساب آخر.
        $taken = User::query()
            ->where('email', $newEmail)
            ->where('id', '!=', $user->getKey())
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('هذا البريد الإلكتروني مسجل بالفعل');
        }

        // (4) slug: المُمرَّر أو مشتق من البريد الجديد + ضمان التفرد.
        $base = trim((string) $requestedSlug) !== ''
            ? Slug::make($requestedSlug)
            : Slug::fromEmail($newEmail);

        $slug = Slug::unique($base, static fn (string $candidate): bool => User::query()
            ->where('slug', $candidate)
            ->where('id', '!=', $user->getKey())
            ->exists());

        // (5) الكتابة في transaction — وإلغاء التوكنات لأن الجلسة مرتبطة بالبريد القديم.
        DB::transaction(static function () use ($user, $newEmail, $slug): void {
            $user->forceFill([
                'email' => $newEmail,
                'slug' => $slug,
                // يفضل غير مؤكد حتى يصل رابط التفعيل الجديد.
                'email_verified_at' => null,
            ])->save();

            $user->tokens()->delete();
        });

        return [
            'success' => true,
            'new_email' => $newEmail,
            'new_slug' => $slug,
        ];
    }
}
