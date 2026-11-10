<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * قوائم الاختيارات الموحدة (قرار 7.1#13 — مهمة ClickUp #6).
 *
 * ⛔ الكود القديم كان يطابق نصوصاً عربية مكتوبة بإملاء مختلف بين
 * الفورم وفلتر الدليل → المطابقة تفشل. الآن كل قائمة لها `code`
 * إنجليزي ثابت (لا يتغير بالتعريب) + `name_ar` للمعرض.
 *
 * المجموعات (group_key):
 *   customer_services, engineer_majors, engineer_specializations,
 *   office_services, exhibition_categories, exhibition_services,
 *   governorates (27), ...
 *
 * UNIQUE(group_key, code) + weight للترتيب + active للإخفاء المؤقت.
 */
class Lookup extends Model
{
    protected $table = 'lookups';

    protected $fillable = [
        'id', 'group_key', 'code', 'name_ar', 'weight', 'active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * عناصر مجموعة نظطة (active فقط) مرتّبة بـ weight.
     *
     * @return Builder
     */
    public function scopeGroup($query, string $groupKey)
    {
        return $query->where('group_key', $groupKey)
            ->where('active', true)
            ->orderBy('weight')
            ->orderBy('id');
    }

    /**
     * بناء خريطة code → name_ar (مفيدة للاستجابات والأخطاء العربية).
     *
     * @return array<string, string>
     */
    public static function mapFor(string $groupKey): array
    {
        return static::query()->group($groupKey)
            ->pluck('name_ar', 'code')
            ->all();
    }
}
