<?php

namespace App\Models;

use App\Enums\PlanType;
use Illuminate\Database\Eloquent\Model;

/**
 * الباقة (SPEC 1.2.10).
 *
 * - UNIQUE(user_type, plan_type, billing_cycle) — seeded يدوياً (الأسعار
 *   غير موجودة في الكود، مأخوذة من القاعدة الحية: 39 صف).
 * - weight للترتيب العرضي (قرار #1) — الترتيب server-side لا بترتيب enum.
 * - الكود لا يكتب في هذا الجدول أبداً (read-only من API).
 * - ⛔ المجانية يحكمها السيرفر من price == 0 (ليست confirmation="free_plan")
 *   — SPEC 2.
 */
class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'id', 'user_type', 'plan_type', 'billing_cycle', 'price',
        'original_price', 'plan_slug', 'duration_days', 'weight',
    ];

    protected function casts(): array
    {
        return [
            'plan_type' => PlanType::class,
            'price' => 'float:2',
            'original_price' => 'float:2',
            'duration_days' => 'integer',
            'weight' => 'integer',
        ];
    }

    /** هل الباقة مجانية؟ — السعر هو المرجع الوحيد (SPEC 2). */
    public function isFree(): bool
    {
        return (float) $this->getAttribute('price') === 0.0;
    }

    /**
     * دورة الفوترة المسموحة لكل نوع حساب.
     * المهندس/المكتب لا يُتاح لهما monthly (SPEC 3.8).
     *
     * @return list<string>
     */
    public static function allowedCyclesFor(string $userType): array
    {
        $all = ['monthly', 'quarter', 'half', 'yearly'];

        return in_array($userType, ['engineer', 'office'], true)
            ? array_values(array_diff($all, ['monthly']))
            : $all;
    }
}
