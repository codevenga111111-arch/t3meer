<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\UserLookupService;
use App\Services\UserProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * المصادقة: تسجيل + دخول + جلسة المستخدم (SPEC 2 + 1.5.7 + مهمة ClickUp #11).
 *
 * - register: منطق `check_user_exists` + توليد slug + خطوة/حالة/باقة
 *   server-side عبر `UserProvisioner` (1.9.3/1.9.16).
 * - login: 401 `invalid_credentials` (نفس الرسالة لغير المسجّل — منع
 *   user enumeration 1.9.6) و403 `email_not_confirmed`.
 * - me: `UserResource` + `next_route` وحقول التوثيق (2.V.6).
 * - logout: إلغاء التوكن الحالي → 204.
 */
class AuthController extends Controller
{
    /**
     * تسجيل حساب جديد — POST /api/v1/auth/register (public، throttle:6,1).
     *
     * Response 201: `{user, token, requires_email_verification}` (SPEC 2).
     */
    public function register(Request $request): JsonResponse
    {
        // prepareForValidation كما في الكود القديم: تطبيع التليفون والبريد
        // قبل القواعد (SPEC 2 — toEnglishDigits + إزالة الفراغات).
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone' => normalizePhone(preg_replace('/\s+/', '', (string) $request->input('phone', ''))),
        ]);

        $validated = $request->validate([
            'account_type' => ['required', 'in:customer,engineer,office,exhibition'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            // رسالة مصرية صريحة بدل "صيغة phone غير صحيحة".
            'phone.regex' => 'رقم الهاتف غير صالح — مثال: 01012345678',
        ]);

        // 409 قبل الإنشاء (SPEC 2: account_exists) — `unique` مش في القواعد
        // عشان التكرر يرجع 409 مش 422.
        if ((new UserLookupService)->exists($validated['email'])) {
            return $this->apiError(
                'هذا الحساب مسجل لدينا بالفعل، يرجى تسجيل الدخول بدلاً من ذلك.',
                409,
                null,
                'account_exists'
            );
        }

        // التليفون له قيد فريد في البيانات — رسالة SPEC الحرفية (422).
        if (User::query()->where('phone', $validated['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الهاتف هذا مسجل لمستخدم آخر بالفعل.',
            ]);
        }

        try {
            $user = (new UserProvisioner)->provision([
                'email' => $validated['email'],
                'user_type' => $validated['account_type'],
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
            ]);
        } catch (InvalidArgumentException $e) {
            // أي رفض من الخدمة (نوع غير صالح…) = 422 برسالة عربية (1.9.16).
            throw ValidationException::withMessages(['account_type' => $e->getMessage()]);
        }

        $user->notify(new VerifyEmailNotification);

        return $this->apiCreated([
            'user' => new UserResource($user),
            'token' => $user->createToken('auth')->plainTextToken,
            'requires_email_verification' => ! $user->hasVerifiedEmail(),
        ], 'تم إنشاء الحساب بنجاح، برجاء تفعيل بريدك الإلكتروني');
    }

    /**
     * تسجيل الدخول — POST /api/v1/auth/login (public، throttle:10,1).
     *
     * Response 200: `{token, user}` — 401 `invalid_credentials` —
     * 403 `email_not_confirmed` (SPEC 2).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        // نفس الرسالة للبريد غير المسجّل وكلمة المرور الخاطئة (1.9.6).
        if (! $user || ! Hash::check($validated['password'], $user->getAuthPassword())) {
            return $this->apiError('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401, null, 'invalid_credentials');
        }

        if (! $user->hasVerifiedEmail()) {
            // الفرونت بيعرض منها خيار الذهاب لصفحة التفعيل (SPEC 2).
            return $this->apiError('يرجى تأكيد بريدك الإلكتروني أولاً', 403, null, 'email_not_confirmed');
        }

        return $this->apiSuccess([
            'token' => $user->createToken('auth')->plainTextToken,
            'user' => new UserResource($user),
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * الجلسة الحالية — GET /api/v1/auth/me (Bearer) → UserResource (SPEC 1.5.7).
     */
    public function me(Request $request): JsonResponse
    {
        return $this->apiSuccess(new UserResource($request->user()));
    }

    /**
     * تسجيل الخروج — POST /api/v1/auth/logout (Bearer) → 204 (SPEC 2).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->apiNoContent('تم تسجيل الخروج');
    }
}
