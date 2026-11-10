<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stats + analytics_counts + interaction_logs + marketing (SPEC 1.2.17–1.2.19).
 *
 * قرارات مطبقة (SPEC 7.1):
 * - #8 الإحصائيات: جدول يومي grain (slug, metric_type, date, count) بدل صف واحد
 *   total يتغيّر created_at كل مرة. UNIQUE(slug, metric_type, date) والزيادة = upsert +1.
 *   الأرقام الثابتة في الرئيسية تُحسم في المراجعة مع المستخدم.
 * - الكتابة في analytics_counts من Laravel (بدل RPC increment_analytics) — ممنوع stored procedures.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255);                      // الملف المستهدف
            $table->enum('metric_type', [
                'visit', 'whatsapp', 'call', 'email', 'map',
                'facebook', 'instagram', 'youtube',
            ]);
            $table->date('date');                             // يوم القياس
            $table->unsignedInteger('count')->default(1);     // عدد الحوادث في ذلك اليوم
            $table->char('user_id', 36)->nullable();          // آخر زائر
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('type', 20)->nullable();           // نوع الهدف
            $table->timestamps();

            $table->unique(['slug', 'metric_type', 'date']);
            $table->index(['metric_type', 'date']);
        });

        Schema::create('analytics_counts', function (Blueprint $table) {
            $table->id();
            $table->enum('stat_type', ['page', 'service', 'governorate', 'sort']);
            $table->string('stat_key', 255);
            $table->unsignedBigInteger('total_count')->default(0);
            $table->timestamps();

            $table->unique(['stat_type', 'stat_key']);
        });

        Schema::create('interaction_logs', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('activity_type', 50)->default('update');
            $table->text('activity_text');
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('marketing', function (Blueprint $table) {
            $table->char('user_id', 36)->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->json('banner')->nullable();               // {title, description, image}
            $table->string('meta_pixel', 100)->nullable();
            $table->json('social')->nullable();               // {facebook, instagram, youtube, meta_id, caption, image}
            $table->enum('plan_type', ['free', 'basic', 'professional', 'premium'])->nullable(); // موجود في البروداكشن
            $table->string('user_type', 20)->nullable();
            $table->string('slug', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing');
        Schema::dropIfExists('interaction_logs');
        Schema::dropIfExists('analytics_counts');
        Schema::dropIfExists('stats');
    }
};
