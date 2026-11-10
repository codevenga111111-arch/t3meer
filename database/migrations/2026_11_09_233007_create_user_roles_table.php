<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_roles — جدول أدوار موجود في بيانات البروداكشن لكنه غائب من SPEC 1.2
 * (ملف Supabase/user_roles_rows.sql: id, user_id, role, created_at).
 *
 * السبب في البناء: دور الأدمن مخزّن هنا بشكل مستقل عن users.user_type
 * (المهندس/المكتب يقدر يكون admin كمان) — middleware role بيقرا من هنا
 * كبديل عن users.user_type، وusers.user_type يفضل نوع الحساب في الدليل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('role', 50);                       // admin, moderator, ...
            $table->timestamps();

            $table->unique(['user_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
