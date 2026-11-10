<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * إعدادات النظام (قرار 7.1#10 — مهمة ClickUp #6).
 *
 * ⛔ الإعدادات كانت مخزّنة في ملف JSON على السيرفر
 * (`data/homepage-display-settings.json`) وبلا أي auth — الآن جدول
 * مع validation وخطأ عند الفشل (SPEC 4, 1.7.4).
 *
 * المفتاح الأساسي: `homepage.display` بقيمة
 * `{offices:{premium,professional,basic,free}, engineers:{...}}`.
 */
class Setting extends Model
{
    protected $table = 'settings';

    /** مفتاح إعدادات ظهور الباقات في الصفحة الرئيسية (SPEC 1.6, 4). */
    public const HOMEPAGE_DISPLAY = 'homepage.display';

    protected $fillable = ['key', 'value'];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * قراءة إعداد مع قيمة افتراضية (لو لم يُضبَط بعد).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->find($key);

        return $setting?->getAttribute('value') ?? $default;
    }

    /**
     * القيم الافتراضية لظهور الباقات: مجاني = false، والباقي true
     * (SPEC 1.6).
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaultHomepageDisplay(): array
    {
        $plans = ['premium' => true, 'professional' => true, 'basic' => true, 'free' => false];

        return ['offices' => $plans, 'engineers' => $plans];
    }
}
