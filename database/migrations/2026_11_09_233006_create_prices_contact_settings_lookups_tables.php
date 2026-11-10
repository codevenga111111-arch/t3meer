<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * materials_prices + contact_messages + settings + lookups
 * (SPEC 1.2.20 + القرارات 7.1#9, #10, #13).
 *
 * - #9  contact_messages: فورم "اتصل بنا" كان لا يحفظ شيئاً — الآن جدول.
 * - #10 settings: إعدادات الرئيسية كانت JSON على السيرفر — الآن جدول.
 * - #13 lookups: قوائم ثابتة (خدمات العميل، تخصصات المهندسين، خدمات المكاتب…)
 *   بـ code ثابت بدل المطابقة بالنص العربي (سبب فشل المطابقة في الكود القديم).
 * - ممنوع triggers: زيادة analytics_counts تتم في Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials_prices', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);                  // مثل: حديد
            $table->string('item_name', 255);
            $table->string('unit', 50);                       // مثل: طن
            $table->decimal('price', 12, 2);
            $table->decimal('change_percent', 6, 2)->default(0);
            $table->timestamps();

            $table->index('category');
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'read', 'replied', 'archived'])->default('new');
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();             // مثل: homepage.display
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50);                  // customer_services, engineer_majors, office_services, exhibition_categories, governorates...
            $table->string('code', 100);                      // مفتاح إنجليزي ثابت للفهرسة والفلترة
            $table->string('name_ar', 255);                   // المعروض بالعربي
            $table->unsignedInteger('weight')->default(0);    // ترتيب العرض
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['group_key', 'code']);
            $table->index('group_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookups');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('materials_prices');
    }
};
