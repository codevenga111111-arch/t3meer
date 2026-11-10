<?php

namespace App\Enums;

/**
 * نوع التفاعل المُتتبَّأ (SPEC 1.4 — enum metric_type، و SPEC 5.3.5).
 *
 * 8 قيم: visit, whatsapp, call, email, map, facebook, instagram, youtube.
 * - `visit` له سلوك خاص: dedupe مرة واحدة في اليوم (SPEC 5.3.5).
 * - باقي الـ metrics بدون dedupe.
 * - ⚠ `phone` مرادف إداري لـ `call` (admin.api.ts:655) — يُطابَق في طبقة
 *   الاستقبال ولا يُخزَّن كقيمة جديدة.
 */
enum MetricType: string
{
    case Visit = 'visit';
    case Whatsapp = 'whatsapp';
    case Call = 'call';
    case Email = 'email';
    case Map = 'map';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Youtube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Visit => 'زيارة',
            self::Whatsapp => 'واتساب',
            self::Call => 'اتصال',
            self::Email => 'بريد',
            self::Map => 'خريطة',
            self::Facebook => 'فيسبوك',
            self::Instagram => 'انستجرام',
            self::Youtube => 'يوتيوب',
        };
    }

    /**
     * الزيارة فقط هي اللي بت dedupe (بالتاريخ + الجهاز/الـ IP).
     * (SPEC 5.3.5).
     */
    public function isDeduplicated(): bool
    {
        return $this === self::Visit;
    }

    /**
     * الـ metrics اللي بتُعرض في عدادات الداشبورد الثمانية
     * (RPC get_limited_stats → {visit, whatsapp, call, email, map,
     * facebook, instagram, youtube} — انظر SPEC 1.4).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * تطبيع المرادفات القادمة من الكود القديم قبل الكتابة.
     * (مثال: phone → call، showrooms-suppliers → exhibition في `type`).
     */
    public static function normalizeAlias(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'phone' => self::Call->value,
            default => $value,
        };
    }
}
