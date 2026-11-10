<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جداول الملفات المهنية الثلاثة: engineers, offices, exhibitions (SPEC 1.2.2–1.2.4).
 *
 * - علاقة 1:1 مع users عبر UNIQUE(user_id) + FK.
 * - slug يُملأ من users.slug عند الإنشاء (لم يكن يكتبه الكود القديم).
 * - text[] (skills, brands) → JSON. jsonb → JSON. numeric → DECIMAL.
 * - camelCase في Postgres (workingHours, coverImage, videoUrl) → snake_case.
 * - جدول materials legacy غير موجود (قرار #2: المورد = exhibition).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineers', function (Blueprint $table) {
            $table->char('id', 36)->primary();                // uuid في بيانات البروداكشن الفعلية
            $table->char('user_id', 36)->unique();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('major', 255)->nullable();          // تخصص حر
            $table->unsignedInteger('experience_years')->default(0);
            $table->string('governorate', 255)->nullable();     // قد يحوي عدة محافظات مفصولة بـ ,
            $table->string('city', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('whats_app', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('working_hours', 255)->nullable();
            $table->json('social')->nullable();                // {facebook, instagram, ...}
            $table->json('skills')->nullable();                // string[]
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('video', 500)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('youtube', 255)->nullable();
            $table->timestamps();

            $table->index('governorate');
            $table->index('major');
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->char('id', 36)->primary();                // uuid في بيانات البروداكشن الفعلية
            $table->char('user_id', 36)->unique();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('governorate', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('whats_app', 20)->nullable();
            $table->unsignedInteger('experience_years')->default(0);
            $table->unsignedInteger('count_of_projects')->default(0); // يدوي من المستخدم
            $table->string('open_hours', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->string('video', 500)->nullable();
            $table->string('facebook', 255)->default('');
            $table->string('instagram', 255)->default('');
            $table->string('youtube', 255)->default('');
            $table->timestamps();

            $table->index('governorate');
        });

        Schema::create('exhibitions', function (Blueprint $table) {
            $table->char('id', 36)->primary();                // uuid في بيانات البروداكشن الفعلية
            $table->char('user_id', 36)->unique();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->nullable()->unique(); // يُكتب هنا فعلاً في الكود
            $table->string('main_category', 255)->nullable();  // label عربي
            $table->string('governorate', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->text('bio')->nullable();
            $table->string('whats_app', 20)->nullable();
            $table->unsignedInteger('experience_years')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->unsignedSmallInteger('count_of_categories')->nullable();
            $table->string('open_hours', 255)->nullable();
            $table->string('video', 500)->nullable();
            $table->json('brands')->nullable();                // string[]
            $table->string('facebook', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('youtube', 255)->nullable();
            $table->timestamps();

            $table->index('governorate');
            $table->index('main_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineers');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('exhibitions');
    }
};
