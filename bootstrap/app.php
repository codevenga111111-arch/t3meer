<?php

use App\Http\Middleware\EnsureOnboardingStep;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'step' => EnsureOnboardingStep::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // كل ردود الـ API بالـ envelope الموحد حتى للأخطاء غير المتوقعة.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // أخطاء المصادقة: 401 بالعربية (SPEC 0.4 — كل رسائل النظام بالعربي).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        });

        // أخطاء التحقق: 422 بالصيغة {message, errors:{field:["رسالة بالعربي"]}}
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => $e->validator->errors()->first() ?: 'بيانات غير صالحة',
                'errors' => $e->validator->errors(),
            ], 422);
        });

        // باقي الأخطاء: {message, code?} بالحالة المناسبة.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $payload = ['message' => $e->getMessage() ?: 'حدث خطأ'];

            $payload['message'] = match ($e->getStatusCode()) {
                401 => 'يجب تسجيل الدخول',
                403 => 'ليس لديك صلاحية لهذا الإجراء',
                404 => 'المسار أو المورد غير موجود',
                405 => 'طريقة الطلب غير مسموح بها',
                429 => 'عدد الطلبات كثيرة، الرجاء المحاولة لاحقاً',
                default => $payload['message'],
            };

            return response()->json($payload, $e->getStatusCode());
        });
    })->create();
