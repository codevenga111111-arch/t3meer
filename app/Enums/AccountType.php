<?php

namespace App\Enums;

/**
 * نوع الحساب (SPEC 1.4 — enum account_type).
 *
 * القرارات المطبقة:
 * - 0.5: لا يوجد material/supplier — المورد والمعارض = exhibition.
 * - القيم = whitelist الـ trigger المُنقّح (SPEC 1.4).
 *
 * ملاحظة: قيم البيانات المستوردة مطابقة بالفعل
 * (engineer:192, customer:71, office:212, exhibition:10, admin:1).
 */
enum AccountType: string
{
    case Customer = 'customer';
    case Engineer = 'engineer';
    case Office = 'office';
    case Exhibition = 'exhibition';
    case Admin = 'admin';

    /**
     * الاسم المعروض بالعربي (لرسائل validation والأخطاء).
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'عميل',
            self::Engineer => 'مهندس',
            self::Office => 'مكتب هندسي',
            self::Exhibition => 'معرض',
            self::Admin => 'مدير النظام',
        };
    }

    /**
     * هل هذا النوع يتطلب توثيقاً (مستندات) قبل التفعيل؟ (SPEC 2.V)
     */
    public function requiresValidation(): bool
    {
        return in_array($this, [self::Engineer, self::Office], true);
    }

    /**
     * هل هذا النوع يمر بمسار الـ onboarding الاحترافي (باقة → ملف)؟
     * العميل له مسار خاص (/customer/setup) — انظر App\Support\Onboarding.
     */
    public function isProfessional(): bool
    {
        return in_array($this, [self::Engineer, self::Office, self::Exhibition], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
