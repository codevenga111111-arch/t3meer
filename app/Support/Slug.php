<?php

namespace App\Support;

use Closure;

/**
 * توليد الـ slug الفريد (المراجع: SPEC 1.8.8, 1.7.8).
 *
 * القاعدة المعتمدة من الكود القديم (generateUniqueSlugFromEmail):
 * 1. نأخذ الجزء قبل @ من الإيميل.
 * 2. نستبدل كل ما ليس [a-zA-Z0-9] بـ "-" ونحذف "-" من الطرفين.
 * 3. لو فاضي نستخدم "user".
 * 4. نضيف "-1", "-2" ... حتى يصبح فريداً.
 */
final class Slug
{
    /** تنظيف نص وإخراج slug صالح: [a-z0-9-]. */
    public static function make(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'user' : $slug;
    }

    /** slug من إيميل: local-part بس. */
    public static function fromEmail(string $email): string
    {
        $local = trim(strstr(trim($email), '@', true) ?: trim($email));

        return self::make($local);
    }

    /**
     * إضافة لاحقة تفرد (-1, -2, ...) حتى يرجع slug غير مستخدم.
     *
     * @param  Closure(string): bool  $exists  يرجع true لو الـ slug محجوز
     */
    public static function unique(string $base, Closure $exists): string
    {
        $base = self::make($base);

        if (! $exists($base)) {
            return $base;
        }

        for ($i = 1; $i < 1000; $i++) {
            $candidate = $base.'-'.$i;

            if (! $exists($candidate)) {
                return $candidate;
            }
        }

        return $base.'-'.uniqid();
    }
}
