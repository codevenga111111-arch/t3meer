<?php

namespace App\Enums;

/**
 * حالة الحساب (SPEC 1.4 — enum status).
 *
 * ⛔ القرار #4: القيمة الموحدة `rejected` — ممنوع كتابة `reject`
 * (الكود القديم كان يكتب reject في admin.api.ts بينما enum SQL يقول rejected).
 *
 * ملاحظة: البيانات المستوردة نظيفة (pending:391, rejected:25, active:70).
 */
enum Status: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'معلق',
            self::Active => 'نشط',
            self::Rejected => 'مرفوض',
            self::Suspended => 'موقوف',
        };
    }

    /**
     * الحسابات اللي بتظهر في الأدلة والصفحة الرئيسية (SPEC 1.3).
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
