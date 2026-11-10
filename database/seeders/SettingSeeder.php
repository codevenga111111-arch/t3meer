<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * إعدادات النظام الافتراضية (قرار 7.1#10 + مهمة ClickUp #7).
 *
 * ⛔ الكود القديم كان يقرأ `data/homepage-display-settings.json` من
 * الـ filesystem بدون أي auth (SPEC 1.6, 4) — الآن جدول settings.
 *
 * المفتاح الوحيد حالياً: `homepage.display`
 *   {offices:{premium,professional,basic,free}, engineers:{...}}
 * الافتراضي: كل الباقات ظاهرة عدا المجانية (free = false) — SPEC 1.6.
 *
 * idempotent: ما يكتبش فوق إعداد عدّله الأدمن (firstOrCreate).
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => Setting::HOMEPAGE_DISPLAY],
            ['value' => Setting::defaultHomepageDisplay()],
        );

        $this->command?->info('+ settings: '.Setting::HOMEPAGE_DISPLAY.' (defaults)');
    }
}
