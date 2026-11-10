<?php

namespace App\Enums;

/**
 * نوع الباقة (SPEC 1.4 — enum plan_type).
 *
 * ⛔ القرار #1: القيمة الموحدة `professional` — ممنوع `pro`
 * (الكود القديم كان يقرأ pro كمرادف؛ التوحيد صار وقت الاستيراد:
 *  subscriptions: professional:9, premium:22, basic:18, free:20).
 *
 * ⚠ الترتيب هنا ليس ترتيب الأولوية — الأولوية من plans.plan_weight
 * (قرار 1.8.12: premium 30 / professional 20 / basic 10 / free 0).
 */
enum PlanType: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Professional = 'professional';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'مجانية',
            self::Basic => 'أساسية',
            self::Professional => 'احترافية',
            self::Premium => 'مميزة',
        };
    }

    /**
     * الوزن المستخدم في الترتيب server-side (SPEC 1.8.12).
     * يُعاد استخدامه بدل ترتيب الـ enum (كان depend على enum order).
     */
    public function weight(): int
    {
        return match ($this) {
            self::Premium => 30,
            self::Professional => 20,
            self::Basic => 10,
            self::Free => 0,
        };
    }

    /**
     * تطبيع القيم القديمة (`pro` → professional) قبل المقارنة/الترتيب.
     * يُستخدم في الفلاتر والـ scopes (SPEC 1.7.4).
     */
    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'pro' => self::Professional->value,
            default => $value,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * ترتيب من الأعلى وزناً للأقل — مفيد للترتيب في SQL عبر CASE.
     *
     * @return list<string>
     */
    public static function orderedByWeight(): array
    {
        return [
            self::Premium->value,
            self::Professional->value,
            self::Basic->value,
            self::Free->value,
        ];
    }
}
