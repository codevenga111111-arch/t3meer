<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * فحص دور المستخدم: role:admin | role:customer,engineer,office,exhibition
 *
 * المرجع: SPEC 0.4 (Roles) و7.3#1 — الدور بيُقرأ من قاعدة البيانات
 * (users.user_type) مش من كوكيز المتصفح زي الكود القديم.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        $userType = (string) $user->getAttribute('user_type');

        // دور الأدمن مسجّل أيضاً في جدول user_roles (بيانات البروداكشن).
        $rolesFromTable = $user->roles()->pluck('role')->all();
        $merged = array_values(array_unique(array_merge([$userType], $rolesFromTable)));

        if (! array_intersect($roles, $merged)) {
            return response()->json(['message' => 'ليس لديك صلاحية لهذا الإجراء'], 403);
        }

        return $next($request);
    }
}
