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
 *   governorates (27), exhibition_categories (23),
 *   exhibition_sub_categories (96 — بـ parent_code للفئة الأم),
 *   engineering_services (16 — موحّدة: تخصصات المهندس + خدمات العميل،
 *   القرار 13: إملاء واحد server-side بدل إملاءين مختلفين),
 *   office_services (8), exhibition_services (14 — قائمة موحّدة 0.5),
 *   engineer_majors (2).
 *
 * UNIQUE(group_key, code) + weight للترتيب + active للإخفاء المؤقت.
 * icon/color من §3.17 لعرض الكتالوج (Iconify id + hex).
 * القيمة المخزنة في جداول البيانات (governorate, service, category)
 * = name_ar للتوافق مع البيانات القديمة؛ الـ code للمطابقة الدقيقة.
 */
class Lookup extends Model
{
    protected $table = 'lookups';

    protected $fillable = [
        'id', 'group_key', 'code', 'name_ar', 'icon', 'color', 'parent_code',
        'weight', 'active',
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
