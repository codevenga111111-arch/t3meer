<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * زرع البيانات المرجعية للقاعدة النظيفة (مهمة ClickUp #7).
 *
 * الترتيب مهم:
 *  1. Plans + UserPermissions + AnalyticsCounts — بيانات مرجعية من
 *     قاعدة البيانات الحية (SPEC 7.2: الأسعار غير موجودة في الكود؛
 *     كلها idempotent وتتخطى لو البيانات موجودة أصلاً).
 *  2. Lookups    — قوائم الاختيارات (لا تعتمد على جداول أخرى).
 *  3. Settings   — إعدادات عرض الصفحة الرئيسية.
 *
 * ملاحظة: بقية بيانات البروداكشن (users, profiles, subscriptions...)
 * تأتي من `php artisan supabase:import` — ليست جزءاً من db:seed.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlansSeeder::class,
            UserPermissionsSeeder::class,
            AnalyticsCountsSeeder::class,
            LookupsSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
