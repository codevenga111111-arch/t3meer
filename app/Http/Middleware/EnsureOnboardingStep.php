<?php

namespace App\Http\Middleware;

use App\Support\Onboarding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فحص خطوة الـ onboarding: step:4 (أقل خطوة مطلوبة)
 *
 * يرجع 409 invalid_onboarding_step + next_route عشان الفرونت يتوجّه
 * (المراجع: SPEC 0.3.1, 1.5.6, 2.0.7 — الانتقالات server-side).
 */
class EnsureOnboardingStep
{
    public function handle(Request $request, Closure $next, int $minStep = 4): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        $currentStep = (int) $user->getAttribute('current_step');
        $userType = (string) $user->getAttribute('user_type');

        if ($currentStep < $minStep) {
            return response()->json([
                'message' => 'يجب إكمال خطوات التسجيل أولاً',
                'code' => 'invalid_onboarding_step',
                'current_step' => $currentStep,
                'required_step' => $minStep,
                'next_route' => Onboarding::stepRoute($userType, $currentStep),
            ], 409);
        }

        return $next($request);
    }
}
