<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * حدود الباقات — صفوف template بـ user_id = NULL (قرار #6 + مهمة #7).
 *
 * القيم من قاعدة البيانات الحية (مطابقة لاستيراد plans_rows.sql).
 * guard: لو فيه template أصلاً (استيراد سابق) → التخطي (نفس منطق
 * ImportSupabaseData::importPlanPermissionTemplates).
 */
class UserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('user_permissions')->whereNull('user_id')->exists()) {
            $this->command?->info('• user_permissions templates موجودة — تم التخطي');

            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        // [user_type, plan_type, max_visits, max_projects, max_services, max_requests,
        //  whatsapp, call, detailed, video, maps, dir_banner, badge, home_banner,
        //  social_promo, pixel, priority_search]
        $data = [
            ['engineer', 'free', null, null, null, 1, 1, 1, 1, 0, 1, 0, 0, 0, 1, 0, 'normal'],
            ['engineer', 'basic', 150, 3, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['engineer', 'basic', 150, 3, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['engineer', 'basic', 150, 3, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['engineer', 'basic', 150, 3, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['engineer', 'professional', 300, 7, 5, 10, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 'medium'],
            ['engineer', 'professional', 300, 7, 5, 10, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 'medium'],
            ['engineer', 'professional', 300, 7, 5, 10, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 'medium'],
            ['engineer', 'professional', 300, 7, 5, 10, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 'medium'],
            ['engineer', 'premium', null, null, null, null, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0, 'high'],
            ['engineer', 'premium', null, null, null, null, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0, 'high'],
            ['engineer', 'premium', null, null, null, null, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0, 'high'],
            ['engineer', 'premium', null, null, null, null, 1, 1, 1, 0, 0, 1, 1, 0, 1, 0, 'high'],
            ['office', 'free', null, null, null, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 'normal'],
            ['office', 'basic', 500, 5, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['office', 'basic', 500, 5, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['office', 'basic', 500, 5, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['office', 'basic', 500, 5, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['office', 'professional', 1000, 10, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['office', 'professional', 1000, 10, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['office', 'professional', 1000, 10, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['office', 'professional', 1000, 10, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['office', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['office', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['office', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['office', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['exhibition', 'free', 500, 10, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['exhibition', 'basic', 500, 10, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['exhibition', 'basic', 500, 10, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['exhibition', 'basic', 500, 10, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['exhibition', 'basic', 500, 10, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'normal'],
            ['exhibition', 'professional', 1000, 20, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['exhibition', 'professional', 1000, 20, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['exhibition', 'professional', 1000, 20, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['exhibition', 'professional', 1000, 20, 7, 10, 1, 1, 0, 1, 0, 1, 0, 0, 0, 0, 'medium'],
            ['exhibition', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['exhibition', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['exhibition', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
            ['exhibition', 'premium', null, null, null, null, 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 'high'],
        ];

        foreach ($data as $r) {
            $rows[] = [
                'user_id' => null,
                'user_type' => $r[0],
                'plan_type' => $r[1],
                'max_visits' => $r[2],
                'max_projects' => $r[3],
                'max_services' => $r[4],
                'max_requests' => $r[5],
                'whatsapp_tracking' => $r[6],
                'call_tracking' => $r[7],
                'detailed_analytics' => $r[8],
                'video_projects' => $r[9],
                'google_maps_location' => $r[10],
                'directory_ad_banner' => $r[11],
                'distinguished_badge' => $r[12],
                'homepage_ad_banner' => $r[13],
                'social_media_promo' => $r[14],
                'facebook_pixel' => $r[15],
                'priority_search' => $r[16],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('user_permissions')->insert($rows);
        $this->command?->info('+ user_permissions: '.count($rows).' صف template');
    }
}
