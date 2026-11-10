<?php

/**
 * دوال مساعدة عامة (المراجع: SPEC 0.4, 4.x — التحقق من الموبايل والبحث).
 */
if (! defined('EMAIL_REGEX')) {
    /** الحد الأدنى للإيميل (مطابق للكود القديم isValidEmail). */
    define('EMAIL_REGEX', '/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
}

if (! function_exists('toEnglishDigits')) {
    /**
     * تحويل الأرقام العربية/الفارسية إلى إنجليزية.
     * مفيد في البحث (SearchView: "010..." == "٠١٠...") وقراءة الموبايل.
     */
    function toEnglishDigits(string $value): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        $value = str_replace($arabic, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $value);
        $value = str_replace($persian, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $value);

        return $value;
    }
}

if (! function_exists('isValidEgyptianPhone')) {
    /**
     * التحقق من الموبايل المصري: يبدأ بـ 01 ثم رقم، وطول 11.
     * (مطابق للكود القديم isValidEgyptianMobile في RegisterView وValidationView)
     */
    function isValidEgyptianPhone(string $phone): bool
    {
        $phone = toEnglishDigits(trim($phone));

        return (bool) preg_match('/^01[0-9]{9}$/', $phone);
    }
}

if (! function_exists('normalizePhone')) {
    /** توحيد صيغة الموبايل: أرقام إنجليزية فقط. */
    function normalizePhone(string $phone): string
    {
        return toEnglishDigits(trim($phone));
    }
}
