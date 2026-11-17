<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T3meer REST API — Version 1
|--------------------------------------------------------------------------
| Base URL: /api/v1
| Auth:     Authorization: Bearer {token}  (Laravel Sanctum)
| Envelope: { "data": ..., "message": "..." }
| Errors:   422 → { "message": "...", "errors": { "field": ["رسالة بالعربي"] } }
| Paging:   ?page=1&per_page=12 → { data, meta:{current_page, per_page, total, last_page} }
|
| ملاحظة: كل endpoints القسم 6 (فهرس الـ Endpoints) هتتسجّل هنا بالترتيب
| في الأيام التالية (Auth = يوم 4 ✅، Onboarding = يوم 5-6، ...).
*/

Route::get('/ping', [HealthController::class, 'ping']);

// ---------------------------------------------------------------------------
// Auth (يوم 4 — المهام 11-13، SPEC 2)
// ---------------------------------------------------------------------------
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1')
    ->name('auth.register');

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('auth.login');

// نسيت كلمة المرور: 200 دائماً (منع user enumeration).
Route::post('/auth/password/forgot', [PasswordController::class, 'forgot'])
    ->middleware('throttle:3,1')
    ->name('auth.password.forgot');

Route::post('/auth/password/reset', [PasswordController::class, 'reset'])
    ->name('auth.password.reset');

// إعادة إرسال تفعيل البريد: الفرونت عنده countdown 60 ثانية → 429 عند التجاوز.
Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:1,1')
    ->name('auth.email.resend');

// رابط التفعيل الموقّع — يوجّه 302 للواجهة (customer/setup أو validation/pricing).
Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('auth.verify-email');

// جلسة المستخدم (Bearer).
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // تغيير بريد حساب غير مؤكد (1.9.5 — إلزام المصادقة).
    Route::patch('/auth/email', [EmailVerificationController::class, 'update'])->name('auth.email.update');

    // تغيير كلمة المرور (3.4.5) + حذف الحساب (3.2.4).
    Route::put('/me/password', [PasswordController::class, 'update'])->name('me.password.update');
    Route::delete('/me', [AccountController::class, 'destroy'])->name('me.destroy');
});
