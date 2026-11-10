<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إضافة أعمدة العرض والهرمية إلى lookups (قرار 7.1#13 + مهمة ClickUp #7).
 *
 * الأسباب:
 * - icon/color: تخصصات المهندس (16) وفئات المعرض (23) وخدماتهم بتتعرض
 *   بـ Iconify id + لون في الكتالوج (SPEC 3.17، 2.875) — بدونهما الـ lookup
 *   لا يصلح للاستهلاك.
 * - parent_code: الفئات الفرعية للمعرض (96 عنصر) مرتبطة بأمها (23 فئة) —
 *   جدول مسطّح بدون عمود أب لا يُستطاع فلترة "بنود فئة X".
 *
 * يُنفَّذ كـ ALTER جديد (لا تعديل على migration القديم) — راجع قاعدة
 * "ال migrations ما تتعملش edit" في الخطة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lookups', 'icon')) {
            return;
        }

        Schema::table('lookups', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->after('name_ar');   // Iconify id
            $table->string('color', 20)->nullable()->after('icon');      // hex مثل #0070d7
            $table->string('parent_code', 100)->nullable()->after('color'); // للفئات الفرعية فقط
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lookups', 'icon')) {
            return;
        }

        Schema::table('lookups', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'parent_code']);
        });
    }
};
