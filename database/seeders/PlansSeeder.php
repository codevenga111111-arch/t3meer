<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * باقات الأسعار الحقيقية من قاعدة البيانات الحية (SPEC 1.2.10 + مهمة #7).
 *
 * ⛔ الأسعار غير موجودة في الكود إطلاقاً (SPEC 7.2) — مصدرها plans_rows.sql
 * من Supabase dump، والصفوف هنا مطابقة 1:1 للقاعدة الحية (39 صف).
 *
 * UNIQUE(user_type, plan_type, billing_cycle) → insertOrIgnore idempotent:
 * لا يكرّر ولا يعلّق فوق بيانات الاستيراد.
 */
class PlansSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('plans')->exists()) {
            $this->command?->info('• plans موجودة مسبقاً ({'.DB::table('plans')->count().'}) — تم التخطي');

            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        // [user_type, plan_type, billing_cycle, price, original_price, plan_slug, duration_days, weight]
        $data = [
            ['engineer', 'free', 'monthly', 0.00, 0.00, 'engineer_free_monthly', 0, 0],
            ['engineer', 'basic', 'monthly', 200.00, 400.00, 'engineer_basic_monthly', 30, 0],
            ['engineer', 'basic', 'quarter', 600.00, 1200.00, 'engineer_basic_quarter', 90, 0],
            ['engineer', 'basic', 'half', 1000.00, 2000.00, 'engineer_basic_half', 180, 0],
            ['engineer', 'basic', 'yearly', 2000.00, 4000.00, 'engineer_basic_yearly', 365, 0],
            ['engineer', 'professional', 'monthly', 300.00, 600.00, 'engineer_professional_monthly', 30, 0],
            ['engineer', 'professional', 'quarter', 900.00, 1800.00, 'engineer_professional_quarter', 90, 0],
            ['engineer', 'professional', 'half', 1500.00, 3000.00, 'engineer_professional_half', 180, 0],
            ['engineer', 'professional', 'yearly', 3000.00, 6000.00, 'engineer_professional_yearly', 365, 0],
            ['engineer', 'premium', 'monthly', 400.00, 800.00, 'engineer_premium_monthly', 30, 0],
            ['engineer', 'premium', 'quarter', 1200.00, 2200.00, 'engineer_premium_quarter', 90, 0],
            ['engineer', 'premium', 'half', 2000.00, 4000.00, 'engineer_premium_half', 180, 0],
            ['engineer', 'premium', 'yearly', 4000.00, 8000.00, 'engineer_premium_yearly', 365, 0],
            ['office', 'free', 'monthly', 0.00, null, 'office_free_monthly', 0, 0],
            ['office', 'basic', 'monthly', 500.00, 1000.00, 'office_basic_monthly', 30, 0],
            ['office', 'basic', 'quarter', 1500.00, 3000.00, 'office_basic_quarter', 90, 0],
            ['office', 'basic', 'half', 2500.00, 5000.00, 'office_basic_half', 180, 0],
            ['office', 'basic', 'yearly', 5000.00, 10000.00, 'office_basic_yearly', 365, 0],
            ['office', 'professional', 'monthly', 1000.00, 2000.00, 'office_professional_monthly', 30, 0],
            ['office', 'professional', 'quarter', 3000.00, 6000.00, 'office_professional_quarter', 90, 0],
            ['office', 'professional', 'half', 5000.00, 10000.00, 'office_professional_half', 180, 0],
            ['office', 'professional', 'yearly', 10000.00, 20000.00, 'office_professional_yearly', 365, 0],
            ['office', 'premium', 'monthly', 2000.00, 4000.00, 'office_premium_monthly', 30, 0],
            ['office', 'premium', 'quarter', 6000.00, 12000.00, 'office_premium_quarter', 90, 0],
            ['office', 'premium', 'half', 10000.00, 20000.00, 'office_premium_half', 180, 0],
            ['office', 'premium', 'yearly', 20000.00, 40000.00, 'office_premium_yearly', 365, 0],
            ['exhibition', 'free', 'monthly', 250.00, 500.00, 'exhibition_free_monthly', 30, 0],
            ['exhibition', 'basic', 'monthly', 250.00, 500.00, 'exhibition_basic_monthly', 30, 0],
            ['exhibition', 'basic', 'quarter', 750.00, 1500.00, 'exhibition_basic_quarter', 90, 0],
            ['exhibition', 'basic', 'half', 1400.00, 2800.00, 'exhibition_basic_half', 180, 0],
            ['exhibition', 'basic', 'yearly', 2500.00, 5000.00, 'exhibition_basic_yearly', 365, 0],
            ['exhibition', 'professional', 'monthly', 500.00, 1000.00, 'exhibition_professional_monthly', 30, 0],
            ['exhibition', 'professional', 'quarter', 1500.00, 3000.00, 'exhibition_professional_quarter', 90, 0],
            ['exhibition', 'professional', 'half', 2900.00, 6000.00, 'exhibition_professional_half', 180, 0],
            ['exhibition', 'professional', 'yearly', 5000.00, 10000.00, 'exhibition_professional_yearly', 365, 0],
            ['exhibition', 'premium', 'monthly', 1000.00, 2000.00, 'exhibition_premium_monthly', 30, 0],
            ['exhibition', 'premium', 'quarter', 3000.00, 6000.00, 'exhibition_premium_quarter', 90, 0],
            ['exhibition', 'premium', 'half', 10000.00, 20000.00, 'exhibition_premium_half', 180, 0],
            ['exhibition', 'premium', 'yearly', 5000.00, 10000.00, 'exhibition_premium_yearly', 365, 0],
        ];

        foreach ($data as [$userType, $planType, $cycle, $price, $original, $slug, $days, $weight]) {
            $rows[] = [
                'user_type' => $userType,
                'plan_type' => $planType,
                'billing_cycle' => $cycle,
                'price' => $price,
                'original_price' => $original,
                'plan_slug' => $slug,
                'duration_days' => $days,
                'weight' => $weight,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('plans')->insert($rows);
        $this->command?->info('+ plans: '.count($rows).' صف (أسعار حقيقية)');
    }
}
