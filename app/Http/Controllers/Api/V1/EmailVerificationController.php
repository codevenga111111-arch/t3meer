<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailChangeService;
use App\Services\UserLookupService;
use App\Support\Onboarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * تفعيل البريد + إعادة الإرسال + تغيير البريد غير المؤكد
 * (SPEC 2 + الإصلاح الأمني 1.9.5 + مهمة ClickUp #12).
 *
 * - verify: رابط موقّع يضبط `email_verified_at` ثم 302 للفرونت حسب النوع —
 *   ⛔ **بدون رفع `current_step`** (2.V.2/2.V.7#2) عشان المهندس/المكتب
 *   يفضلوا step 1 و`next_route` = `/validation/{type}`.
 * - resend: throttle:1,1 (الفرونت عنده countdown 60 ثانية) → 429 عند التجاوز.
 * - update: Bearer إلزامي + حساب غير مؤكد فقط — الإصلاح للثغرة
 *   `GRANT EXECUTE … TO anon` على الـ RPC القديم (1.9.5).
 */
class EmailVerificationController extends Controller
{
    /**
     * تفعيل البريد عبر الرابط الموقّع — GET /api/v1/auth/email/verify/{id}/{hash}.
     *
     * `signed` middleware بيرفض أي تعديل على `{id}/{hash}` بـ 403، والفحص
     * هنا defense-in-depth (hash = sha1(email) كما في Supabase).
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);

        if (! $user) {
            abort(404, 'الحساب غير موجود');
        }

        if (! hash_equals(sha1((string) $user->getAttribute('email')), $hash)) {
            abort(403, 'رابط التفعيل غير صالح');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // 302 للواجهة: customer → /customer/setup، غيره → /validation/{type}
        // للمهندس/المكتب أو /pricing/{type} للبقية (2.V.2 — خطوة 1).
        return redirect()->away(
            config('app.url').Onboarding::stepRoute($user->userTypeValue(), (int) $user->getAttribute('current_step'))
        );
    }

    /**
     * إعادة إرسال رابط التفعيل — POST /api/v1/auth/email/resend (throttle:1,1).
     *
     * Existence check موجود في القواعد بنفسه (SPEC 2 — `exists:users,email`).
     */
    public function resend(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'account_type' => ['nullable', 'in:customer,engineer,office,exhibition'],
        ]);

        $user = (new UserLookupService)->find($validated['email']);

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->notify(new VerifyEmailNotification);
        }

        return $this->apiSuccess(null, 'تم إرسال رابط التفعيل إلى بريدك الإلكتروني');
    }

    /**
     * تغيير بريد حساب غير مؤكد — PATCH /api/v1/auth/email (Bearer).
     *
     * Response: `{success, new_email, new_slug, email_sent, message}` (SPEC 2).
     * المنطق كله في `EmailChangeService` (إعادة توليد slug + إلغاء الجلسات).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            $result = (new EmailChangeService)->change($request->user(), $validated['new_email']);
        } catch (InvalidArgumentException $e) {
            // مؤكد بالفعل / مطابق / مسجّل لآخر → 422 برسالة عربية.
            throw ValidationException::withMessages(['new_email' => $e->getMessage()]);
        }

        // الخدمة لغت كل التوكنات (الجلسة مرتبطة بالبريد القديم) — نرسل
        // رابط التفعيل للبريد الجديد من الحساب نفسه.
        $user = $request->user()->refresh();
        $user->notify(new VerifyEmailNotification);

        return $this->apiSuccess([
            'success' => true,
            'new_email' => $result['new_email'],
            'new_slug' => $result['new_slug'],
            'email_sent' => true,
            'message' => 'تم تغيير البريد الإلكتروني، برجاء تفعيله من الرابط المرسل',
        ], 'تم تغيير البريد الإلكتروني');
    }
}
