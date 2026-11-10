<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فصل rating.type ذو الدلالة المزدوجة إلى عمودين (SPEC 1.2.16 + مهمة ClickUp #5).
 *
 * المشكلة الأصلية:
 * - في تقييم ملف:  rating.type = نوع الهدف  (engineer/office/exhibition)
 * - في تقييم المنصة (slug="t3meer"): rating.type = نوع الكاتب (user_type)
 * → عمود واحد بمعنيين = خطأ في أي استعلام يفلتر عليه.
 *
 * الفصل:
 * - target_type: نوع الهدف (engineer/office/exhibition/platform).
 * - writer_type: نوع الكاتب (customer/engineer/office/exhibition).
 *
 * ⛔ عمود type القديم يُحذف بعد النقل — إبقاء عمود مهجور بمعنى غامض
 * يعيد الخلط نفسه. المصدر الخام محفوظ في Supabase/rating_rows.sql.
 *
 * تنفيذه كـ ALTER جديد لأن القاعدة تحتوي بيانات مُستوردة فعلاً (6 صفوف).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('rating', 'target_type')) {
            return;
        }

        Schema::table('rating', function (Blueprint $table) {
            $table->enum('target_type', ['engineer', 'office', 'exhibition', 'platform'])
                ->nullable()->after('slug');
            $table->enum('writer_type', ['customer', 'engineer', 'office', 'exhibition', 'admin'])
                ->nullable()->after('user_id');
            $table->index('target_type');
        });

        // 1) تقييمات المنصة: slug = t3meer ← الهدف platform، والـ type القديم = الكاتب.
        DB::table('rating')->where('slug', 't3meer')->update([
            'target_type' => 'platform',
            'writer_type' => DB::raw('`type`'),
        ]);

        // 2) تقييمات الملفات: الـ type القديم = نوع الهدف.
        DB::table('rating')->where('slug', '!=', 't3meer')
            ->whereIn('type', ['engineer', 'office', 'exhibition'])
            ->update(['target_type' => DB::raw('`type`')]);

        // 3) نوع الكاتب في تقييمات الملفات ← من users.user_type،
        //    وأي صف بـ target_type ناقص يُستكمل (row ?? writer).
        $users = DB::table('users')->pluck('user_type', 'id');
        DB::table('rating')->where('slug', '!=', 't3meer')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($users) {
                foreach ($rows as $row) {
                    $writer = $users->get($row->user_id);
                    DB::table('rating')->where('id', $row->id)->update([
                        'writer_type' => $writer,
                        // لو type القديم لم يكن نوع هدف صالح → نُهيّئه من الكاتب.
                        'target_type' => $row->target_type ?? $writer,
                    ]);
                }
            });

        Schema::table('rating', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('rating', 'target_type')) {
            return;
        }

        Schema::table('rating', function (Blueprint $table) {
            $table->enum('type', ['customer', 'engineer', 'office', 'exhibition'])
                ->nullable()->after('slug');
        });

        // إعادة الدمج: تقييم المنصة يأخذ نوع الكاتب، وغيره يأخذ نوع الهدف.
        DB::table('rating')->where('target_type', 'platform')
            ->update(['type' => DB::raw('`writer_type`')]);
        DB::table('rating')->where('target_type', '!=', 'platform')
            ->orWhereNull('target_type')
            ->update(['type' => DB::raw('`target_type`')]);

        Schema::table('rating', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'writer_type']);
        });
    }
};
