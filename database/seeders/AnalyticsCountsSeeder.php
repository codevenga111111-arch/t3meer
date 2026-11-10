<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * عدادات التحليلات الثابتة من القاعدة الحية (SPEC 1.2.18 + مهمة #7).
 *
 * الأرقام دي هي "الأرقام الثابتة في الرئيسية" (قرار 7.1#8) —
 * تُزرع مرة في القاعدة النظيفة لتبقى أرقام الموقع الحقيقية.
 *
 * UNIQUE(stat_type, stat_key) → insertOrIgnore idempotent
 * (لا يعدّل عدّاداً نشئه الـ import أو التتبع الحي).
 */
class AnalyticsCountsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('analytics_counts')->exists()) {
            $this->command?->info('• analytics_counts موجودة — تم التخطي');

            return;
        }

        $rows = [];

        // [stat_type, stat_key, total_count]
        $data = [
            ['page', 'engineers', 602],
            ['page', 'home', 3868],
            ['page', 'offices', 758],
            ['page', 'showrooms-suppliers', 176],
            ['service', 'إدارة مشاريع', 17],
            ['service', 'استشارات هندسية', 9],
            ['service', 'اعمال ترميم', 3],
            ['service', 'اعمال خرسانات', 5],
            ['service', 'اعمال عزل', 2],
            ['service', 'الأبواب والشبابيك', 3],
            ['service', 'الأثاث والفرش', 1],
            ['service', 'الأخشاب', 2],
            ['service', 'الأمن والسلامة', 1],
            ['service', 'الإضاءة', 1],
            ['service', 'الجبس والأسقف', 1],
            ['service', 'الحديد والمعادن', 3],
            ['service', 'الخرسانة والإنشاءات', 4],
            ['service', 'الدهانات والديكور', 2],
            ['service', 'السباكة والمواسير', 3],
            ['service', 'العزل', 1],
            ['service', 'الكهرباء', 1],
            ['service', 'المطابخ والدريسنج', 2],
            ['service', 'تراخيص بناء', 9],
            ['service', 'تشطيبات', 77],
            ['service', 'تصالح للبناء', 4],
            ['service', 'تصميم انشائي', 5],
            ['service', 'تصميم داخلي', 34],
            ['service', 'تصميم معماري', 5],
            ['service', 'تصميم واجهات', 3],
            ['service', 'حصر كميات', 18],
            ['service', 'خدمات نقابية', 1],
            ['service', 'رسومات تنفيذية', 13],
            ['service', 'رفع مساحة', 1],
            ['service', 'مواد البناء الأساسية', 6],
            ['governorate', 'أسوان', 3],
            ['governorate', 'أسيوط', 11],
            ['governorate', 'الأقصر', 6],
            ['governorate', 'الإسكندرية', 56],
            ['governorate', 'الإسماعيلية', 7],
            ['governorate', 'البحر الأحمر', 9],
            ['governorate', 'البحيرة', 5],
            ['governorate', 'الجيزة', 35],
            ['governorate', 'الدقهلية', 40],
            ['governorate', 'السويس', 9],
            ['governorate', 'الشرقية', 31],
            ['governorate', 'الغربية', 19],
            ['governorate', 'الفيوم', 6],
            ['governorate', 'القاهرة', 88],
            ['governorate', 'القليوبية', 14],
            ['governorate', 'المنوفية', 12],
            ['governorate', 'المنيا', 9],
            ['governorate', 'الوادي الجديد', 1],
            ['governorate', 'بني سويف', 11],
            ['governorate', 'بورسعيد', 9],
            ['governorate', 'جنوب سيناء', 1],
            ['governorate', 'دمياط', 15],
            ['governorate', 'سوهاج', 9],
            ['governorate', 'شمال سيناء', 2],
            ['governorate', 'قنا', 6],
            ['governorate', 'كفر الشيخ', 12],
            ['governorate', 'مطروح', 2],
            ['sort', 'bestRating', 33],
            ['sort', 'bestViewers', 19],
            ['sort', 'experience', 26],
            ['sort', 'nearMe', 40],
        ];

        foreach ($data as [$type, $key, $total]) {
            $rows[] = ['stat_type' => $type, 'stat_key' => $key, 'total_count' => $total];
        }

        DB::table('analytics_counts')->insert($rows);
        $this->command?->info('+ analytics_counts: '.count($rows).' صف');
    }
}
