<?php

namespace App\Services;

use App\Models\User;

/**
 * البحث عن حساب بالبريد — بديل RPC `check_user_exists` (SPEC 1.4 + مهمة ClickUp #9).
 *
 * ⛔ الإصلاح الأمني 1.9.6: الـ RPC القديم كان متاح لمجهول الـ anon key ويرجع
 * `user_id` لكل من يعرف بريداً = user enumeration + تسريب UUID. هنا:
 * - `exists()` = الدليل الوحيد المسموح به في أي استدعاء عام (التسجيل يوم 4).
 * - `resolveId()` / `find()` = للاستخدام الداخلي فقط (الأدمن + الخدمات)،
 *   وممنوع expose في endpoint غير محمي.
 *
 * التطبيع مطابق للـ SQL القديم `lower(trim(p_email))` — trim + lowercase.
 * ⚠ البريد **يُخزَّن lowercase** في `users.email` (register والاستيراد بيطبّعوه)،
 * فالبحث بـ `where('email', ...)` يستخدم الفهرس الفريد ويتطابق على الاتنين.
 */
final class UserLookupService
{
    /** تطبيع البريد: إزالة الفراغات + أحرف صغيرة. */
    public function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * هل يوجد حساب بهذا البريد؟ (بديل `exists` من الـ RPC).
     * الفارغ يرجع false بدل استعلام بلا معنى.
     */
    public function exists(string $email): bool
    {
        $email = $this->normalize($email);

        if ($email === '') {
            return false;
        }

        return User::query()->where('email', $email)->exists();
    }

    /** جلب الحساب بالبريد — للاستخدام الداخلي فقط (ممنوع في رد عام). */
    public function find(string $email): ?User
    {
        $email = $this->normalize($email);

        if ($email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    /**
     * id الحساب المرتبط بالبريد — بديل `user_id` في رد الـ RPC (للاستخدام
     * الداخلي/الأدمن فقط: admin.api.ts:1652,1909).
     *
     * ⛔ أي endpoint يستدعيها لغير مُصادَق عليه = تسريب (1.9.6).
     */
    public function resolveId(string $email): ?string
    {
        $user = $this->find($email);

        return $user?->getAttribute('id');
    }
}
