<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_validation + plans + subscriptions + subscription_payments + user_permissions
 * (SPEC 1.2.9–1.2.13).
 *
 * قرارات مطبقة (SPEC 7.1):
 * - #1 قيم الباقات الموحّدة: free, basic, professional, premium + عمود weight للترتيب.
 * - #3 التوثيق خطوة رسمية للمهندس والمكتب، والأدمن فقط بيغيّح الحالة.
 * - #4 حالات موحدة: validation = pending/active/rejected (بدون "reject").
 * - #5 إعادة تصميم subscriptions:
 *     · إزالة التكرار: plan_type فقط (بدون plan_id)، starts_at/expires_at فقط
 *       (بدون start_date/end_date)، price فقط (بدون plan_price/amount).
 *     · السعر والتواريخ تُحسب على السيرفر لا من الفرونت.
 * - #6 user_permissions يُملأ seed لكل باقة ويُطبَّق في الـ API (وليس client-side).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_validation', function (Blueprint $table) {
            $table->char('id', 36)->primary();                // uuid في بيانات البروداكشن الفعلية
            $table->char('user_id', 36)->unique();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);                       // الاسم القانوني
            $table->enum('account_type', ['engineer', 'office', 'exhibition'])->nullable();
            $table->string('professional_id', 100)->nullable(); // رقم العضوية/السجل التجاري
            $table->string('tax_number', 100)->nullable();
            $table->json('documents')->nullable();             // مصفوفة URLs
            $table->json('details')->nullable();               // {graduation_year, specialization} / {tax_id}
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['engineer', 'office', 'exhibition']);
            $table->enum('plan_type', ['free', 'basic', 'professional', 'premium']); // قرار #1
            $table->enum('billing_cycle', ['monthly', 'quarter', 'half', 'yearly']);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('plan_slug', 255)->nullable();      // موجود في بيانات البروداكشن
            $table->unsignedInteger('duration_days')->nullable(); // أيام الاشتراك (من billing_cycle)
            $table->unsignedInteger('weight')->default(0);      // ترتيب العرض (قرار #1)
            $table->timestamps();

            $table->unique(['user_type', 'plan_type', 'billing_cycle']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->unique();             // صف واحد لكل مستخدم
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('plan_type', ['free', 'basic', 'professional', 'premium']);
            $table->string('plan_slug', 255)->nullable();
            $table->enum('billing_cycle', ['monthly', 'quarter', 'half', 'yearly'])->nullable();
            $table->string('user_type', 20)->nullable();
            $table->decimal('price', 10, 2)->nullable();       // يُحسب على السيرفر (قرار #5)
            $table->string('confirmation', 500)->nullable();   // URL إيصال أو "free_plan"
            $table->enum('status', ['pending', 'active', 'expired', 'rejected'])->default('pending');
            $table->timestamp('starts_at')->nullable();        // يُحسب على السيرفر
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_renewal')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->char('id', 36)->primary();                // uuid في بيانات البروداكشن الفعلية
            $table->char('subscription_id', 36)->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            // nullable: مدفوعات حسابات محذوفة من المصدر — نُبقي السجل المالي (تقرير الاستيراد).
            $table->char('user_id', 36)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('plan_type', ['free', 'basic', 'professional', 'premium']);
            $table->enum('billing_cycle', ['monthly', 'quarter', 'half', 'yearly'])->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'rejected'])->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36)->nullable()->unique(); // صفوف template بـ null ممكنة
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('user_type', ['customer', 'engineer', 'office', 'exhibition'])->nullable();
            $table->enum('plan_type', ['free', 'basic', 'professional', 'premium']);

            // الحدود: null أو -1 أو >=99999 = غير محدود (تُطبَّق في الـ API الآن).
            $table->integer('max_visits')->nullable();
            $table->integer('max_projects')->nullable();
            $table->integer('max_services')->nullable();
            $table->integer('max_requests')->nullable();       // حد طلبات العملاء (موجود في البروداكشن)

            // Feature flags (SPEC 1.7.5).
            $table->boolean('whatsapp_tracking')->default(false);
            $table->boolean('call_tracking')->default(false);
            $table->boolean('detailed_analytics')->default(false);
            $table->boolean('video_projects')->default(false);
            $table->boolean('google_maps_location')->default(false);
            $table->boolean('directory_ad_banner')->default(false);
            $table->boolean('distinguished_badge')->default(false);
            $table->boolean('homepage_ad_banner')->default(false);
            $table->boolean('social_media_promo')->default(false);
            $table->boolean('facebook_pixel')->default(false);

            $table->enum('priority_search', ['normal', 'medium', 'high'])->default('normal'); // موحّد case
            $table->timestamps();

            $table->index(['user_type', 'plan_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('user_validation');
    }
};
