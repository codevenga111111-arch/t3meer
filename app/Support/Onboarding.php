<?php

namespace App\Support;

/**
 * آلة حالات الـ onboarding: تحويل (user_type, current_step) إلى مسار.
 *
 * المرجع: SPEC 1.5.6 (جدول getStepRoute) + 2.V.2 (خطوة التوثيق
 * لازم ترجع عند step 1 للمهندس والمكتب).
 */
final class Onboarding
{
    /** خطوة 1: تم التسجيل (مطلوب تأكيد البريد). */
    public const STEP_REGISTERED = 1;

    /** خطوة 2: المهني اطّلع على الأسعار / أنهى التوثيق — العميل أنهى الإعداد. */
    public const STEP_PLAN_BROWSING = 2;

    /** خطوة 3: اختار باقة بتأكيد (مجانية أو إيصال). */
    public const STEP_PLAN_SELECTED = 3;

    /** خطوة 4: أكمل الملف المهني (جاهز للداشبورد). */
    public const STEP_PROFILE_COMPLETED = 4;

    /**
     * المسار المناسب للمستخدم في خطوته الحالية.
     * الفرونت يستهلك next_route من GET /api/v1/auth/me ولا يكرر getStepRoute().
     */
    public static function stepRoute(string $userType, int $step): string
    {
        if ($userType === 'customer') {
            return $step <= self::STEP_REGISTERED ? '/customer/setup' : '/dashboard/customer';
        }

        if ($userType === 'admin') {
            return '/dashboard/admin';
        }

        // المهنيون: engineer / office / exhibition
        if ($step <= self::STEP_PLAN_BROWSING) {
            // التوثيق إلزامي للمهندس والمكتب عند الخطوة الأولى (SPEC 2.V.2)
            if ($step <= self::STEP_REGISTERED && in_array($userType, ['engineer', 'office'], true)) {
                return '/validation/'.$userType;
            }

            return '/pricing/'.$userType;
        }

        if ($step === self::STEP_PLAN_SELECTED) {
            return '/complete-profile/'.$userType;
        }

        return '/dashboard/'.$userType;
    }

    /** أقل خطوة تسمح بالدخول للداشبورد. */
    public static function dashboardMinStep(string $userType): int
    {
        return $userType === 'customer' ? self::STEP_PLAN_BROWSING : self::STEP_PROFILE_COMPLETED;
    }
}
