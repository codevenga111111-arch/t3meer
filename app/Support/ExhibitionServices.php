<?php

namespace App\Support;

/**
 * الخدمات الموحدة للمعرض/المورد — القرار 0.5 (SPEC 0.5 + مهمة ClickUp #10).
 *
 * المصدر الوحيد لحقيقة «ما هي خدمات المعرض»: 14 خدمة = 8 خدمات المعرض أصلًا
 * + 6 من خدمات المورد، والقيمتين المكررتين تُدمجان وقت نقل البيانات:
 *   site-delivery (توصيل للموقع) → delivery-shipping (توصيل وشحن)
 *   wholesale-prices (أسعار جملة) → wholesale (بيع بالجملة)
 *
 * المستهلكون:
 * - LookupsSeeder: يزرع قائمة الـ14 (code + label + icon/color).
 * - t3meer:merge-materials: يحوّل materials.specializations (كود قديم)
 *   إلى صفوف services بالـ label الموحد.
 *
 * المرجع: T3MEER_BACKEND_API_SPEC_v3.md
 * - 0.5 (قائمة الخدمات الموحدة + خريطة الـ map).
 * - 3.258 / 2745 (materialServices: القيم المخزنة = الـ value وليس الـ label).
 */
final class ExhibitionServices
{
    /**
     * أيقونة الخدمات المخصصة (SPEC 1.2.6 — نفس أيقونة المعرض في الواجهة).
     */
    public const ICON = 'solar:double-alt-arrow-right-bold';

    /**
     * لون الخدمات المخصصة (SPEC 1.2.6).
     */
    public const COLOR = '#FC7753';

    /**
     * القيم المكررة القديمة ← الكود الموحد (يُطبَّق وقت نقل البيانات — SPEC 0.5).
     *
     * @var array<string, string>
     */
    public const LEGACY_MAP = [
        'site-delivery' => 'delivery-shipping',
        'wholesale-prices' => 'wholesale',
    ];

    /**
     * الخدمات الموحدة الـ14: الكود ← الاسم العربي
     * (8 خدمات المعرض + 6 من المورد — قرار: الموحدة هي المعتمدة).
     *
     * @var array<string, string>
     */
    public const ALL = [
        // خدمات المعرض الثمانية (كما هي).
        'delivery-shipping' => 'توصيل وشحن',
        'installation' => 'تركيب',
        'design-consulting' => 'استشارات تصميم',
        'custom-manufacturing' => 'تصنيع حسب الطلب',
        'product-warranty' => 'ضمان على المنتجات',
        'wholesale' => 'بيع بالجملة',
        'inspection-measurement' => 'معاينة وقياسات',
        'after-sales-service' => 'خدمة ما بعد البيع',
        // الست من خدمات المورد (يُضافن للمعرض).
        'payment-plans' => 'تسهيلات سداد',
        'free-samples' => 'عينات مجانية',
        'technical-support' => 'دعم فني',
        'quality-certificates' => 'شهادات جودة',
        'return-policy' => 'سياسة إرجاع',
        'supply-contracts' => 'عقود توريد',
    ];

    /**
     * قائمة materialServices القديمة (8 قيم + labels) — مرجع لقراءة
     * materials.specializations والـ services المكتوبة بالـ label القديم.
     *
     * @var array<string, string>
     */
    public const LEGACY_MATERIAL = [
        'site-delivery' => 'توصيل للموقع',
        'wholesale-prices' => 'أسعار جملة',
        'payment-plans' => 'تسهيلات سداد',
        'free-samples' => 'عينات مجانية',
        'technical-support' => 'دعم فني',
        'quality-certificates' => 'شهادات جودة',
        'return-policy' => 'سياسة إرجاع',
        'supply-contracts' => 'عقود توريد',
    ];

    /**
     * كل الأكواد المدعومة (الـ14).
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * الكود الموحد لقيمة قديمة — القيمة إما كود (`site-delivery`) أو label عربي
     * («توصيل للموقع») من قوائم المعرض أو المورد.
     *
     * @return string|null null لو القيمة مجهولة (تُترك custom في services).
     */
    public static function resolve(mixed $raw): ?string
    {
        if (! is_scalar($raw)) {
            return null;
        }

        $value = trim((string) $raw);
        $code = strtolower($value);

        if ($value === '') {
            return null;
        }

        if (isset(self::LEGACY_MAP[$code])) {
            return self::LEGACY_MAP[$code];
        }

        if (isset(self::ALL[$code])) {
            return $code;
        }

        foreach (self::ALL as $candidate => $label) {
            if (trim($label) === $value) {
                return $candidate;
            }
        }

        // label قديم من materialServices ← كوده الموحد.
        foreach (self::LEGACY_MATERIAL as $candidate => $label) {
            if (trim($label) === $value) {
                return self::LEGACY_MAP[$candidate] ?? $candidate;
            }
        }

        return null;
    }

    /**
     * الـ label العربي الموحد لقيمة قديمة (كود أو label).
     */
    public static function label(mixed $raw): ?string
    {
        $code = self::resolve($raw);

        return $code === null ? null : self::ALL[$code];
    }

    /**
     * هل الكود من الـ14 الموحدة؟ (للاستعلامات السريعة قبل الكتابة).
     */
    public static function isSupported(string $code): bool
    {
        return isset(self::ALL[$code]);
    }
}
