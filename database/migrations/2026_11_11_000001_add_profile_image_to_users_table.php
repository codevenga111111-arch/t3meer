<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة users.profile_image (SPEC 1.2.1 + 1.5.4 + مهمة ClickUp #4).
 *
 * القصد: مفاتيح user_metadata اللي كانت metadata فقط وبقت أعمدة:
 *   selected_billing_cycle ✅ (اتعملت في migration الأساسي)
 *   selected_user_type     ✅ (اتعملت في migration الأساسي)
 *   profile_image/image    ⛔ كانت ناقصة ← هذا المigration.
 *
 * ⚠ ملاحظة تصميم: الكود القديم يكتب الصورة في جدول البروفايل (1.5.4)
 * وليس في users، فهذا العمود يُملأ backfill من جداول المهندس/المكتب/المعرض
 * ويظل mirror فقط — مصدر الحقيقة هو جدول البروفايل، وعند تعديل الصورة
 * (SPEC 3.2.2) يُحدَّث الجدولان في نفس الـ transaction.
 *
 * تنفيذه كـ ALTER جديد (لا تعديل على migration قديم) لأن القاعدة تحتوي
 * 486 مستخدماً مُستورداً فعلاً.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'profile_image')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image', 500)->nullable()->after('rejection_reason');
        });

        // backfill: أول صورة متاحة من جداول البروفايل (نفس slug/user_id).
        $sources = [
            'engineers' => 'profile_image',
            'offices' => 'profile_image',
            'exhibitions' => 'logo', // المعرض: logo هو الصورة المميزة (SPEC 2)
        ];

        foreach ($sources as $table => $column) {
            // backfill على دفعات — الترتيب ثابت (id) لتفادي تكرار/فقد صفوف.
            DB::table($table)
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($column) {
                    foreach ($rows as $row) {
                        DB::table('users')
                            ->where('id', $row->user_id)
                            ->whereNull('profile_image')
                            ->update(['profile_image' => $row->{$column}]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'profile_image')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }
    }
};
