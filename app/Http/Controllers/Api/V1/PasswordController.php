<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * نسيت/تغيير كلمة المرور (SPEC 2 + 3.4.5 + مهمة ClickUp #13).
 *
 * - forgot: **200 دائماً** لمنع user enumeration (زي Supabase) — حتى لو
 *   البريد غير مسجّل أو الرابط مُرسَل قبل قليل (broker throttle).
 * - reset: 422 لو التوكن غير صالح/منتهٍ (password_reset_tokens).
 * - update: `current_password` إلزامي (الكود القديم لا يطلبها — 3.4.5).
 */
class PasswordController extends Controller
{
    /**
     * طلب رابط إعادة التعيين — POST /api/v1/auth/password/forgot (throttle:3,1).
     *
     * الرابط في الإيميل: `{APP_URL}/reset-password?token={token}&email={email}`.
     */
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        // نفس الرد للحالتين (200) — والـ broker throttle (60 ثانية) مش بيغيّر
        // الرد عشان ما نكشفش إن البريد مسجّل.
        Password::broker()->sendResetLink([
            'email' => strtolower(trim($validated['email'])),
        ]);

        return $this->apiSuccess(null, 'إذا كان البريد مسجّلاً لدينا فسيصلك رابط إعادة تعيين كلمة المرور');
    }

    /**
     * تنفيذ إعادة التعيين — POST /api/v1/auth/password/reset (public).
     *
     * Response 200 — **422** لو التوكن غير صالح أو منتهي (SPEC 2).
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::broker()->reset([
            'email' => strtolower(trim($validated['email'])),
            'token' => $validated['token'],
            'password' => $validated['password'],
        ], function ($user, string $password): void {
            $user->forceFill(['password' => $password])->save();
            // الجلسات القديمة تنتهي مع تغيير كلمة المرور.
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            // رسالة واحدة للحالتين (token غير صالح/منتهي أو بريد غير مسجّل)
            // — بدون كشف وجود الحساب.
            throw ValidationException::withMessages([
                'token' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية.',
            ]);
        }

        return $this->apiSuccess(null, 'تم إعادة تعيين كلمة المرور بنجاح');
    }

    /**
     * تغيير كلمة المرور للمستخدم المسجّل — PUT /api/v1/me/password (Bearer).
     *
     * `current_password` إلزامية → 204 (SPEC 3.4.5).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        // فحص يدوي بدل قاعدة `current_password` — دي بتعتمد على الـ guard
        // الافتراضي (web/session) ودي API بالـ Bearer.
        if (! Hash::check($validated['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => 'كلمة المرور الحالية غير صحيحة.',
            ]);
        }

        $user->forceFill(['password' => $validated['password']])->save();

        // الجلسة الحالية تفضل شغالة (التغيير من الإعدادات)، والباقي يُلغى
        // عند تسجيل الدخول من جديد.
        return $this->apiNoContent('تم تغيير كلمة المرور');
    }
}
