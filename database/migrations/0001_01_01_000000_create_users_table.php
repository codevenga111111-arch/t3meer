<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول المستخدمين المركزي (SPEC 1.2.1).
 *
 * قرارات مطبقة (SPEC 7.1):
 * - #1  أسماء الباقات الموحدة: free, basic, professional, premium (+ weight في plans).
 * - #2  لا يوجد نوع "material/supplier" — المورد = exhibition.
 * - #4  قيم حالات موحدة: status = pending, active, rejected, suspended (بدون "reject").
 * - #14 التليفونات تُخزن normalized (010...) — التحقق في Laravel helpers.
 *
 * ملاحظات تحويل Postgres → MySQL:
 * - uuid PK → CHAR(36) يولّده الـ server بـ UUID v4 (App\Models\User::booted).
 * - timestamptz → TIMESTAMP.
 * - ممنوع triggers: كل اللوجيك (slug, metadata) في Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // UUID v4 يُولَّد من السيرفر (لا يأتي من الـ body أبداً).
            $table->char('id', 36)->primary();
            $table->string('email', 255)->unique();
            $table->string('password')->nullable(); // nullable: قبل تعيين كلمة المرور
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name', 255)->default('مستخدم');
            $table->string('phone', 20)->nullable()->unique();

            // نوع الحساب — بدون material/supplier (قرار #2).
            $table->enum('user_type', ['customer', 'engineer', 'office', 'exhibition', 'admin'])
                ->default('engineer');

            $table->string('slug', 255)->unique()->index();

            // محرك الـ onboarding (0–4) — الانتقالات server-side.
            $table->unsignedTinyInteger('current_step')->default(1);

            // قيم حالات موحدة (قرار #4): rejected بدل reject.
            $table->enum('status', ['pending', 'active', 'rejected', 'suspended'])
                ->default('pending');

            // قيم الباقات الموحدة (قرار #1): professional بدل pro.
            $table->enum('selected_plan', ['free', 'basic', 'professional', 'premium'])
                ->default('free');

            // كانت في user_metadata فقط وأصبحت أعمدة (SPEC 1.2.1).
            $table->enum('selected_billing_cycle', ['monthly', 'quarter', 'half', 'yearly'])
                ->nullable();
            $table->string('selected_user_type', 20)->nullable();

            $table->text('rejection_reason')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->char('user_id', 36)->nullable()->index(); // CHAR(36) مطابق لـ users.id
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
