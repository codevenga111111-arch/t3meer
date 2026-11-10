<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customers + customer_request_assignments + favorites + rating
 * (SPEC 1.2.14–1.2.16).
 *
 * قرارات مطبقة (SPEC 7.1):
 * - #7 جدول history للترشيحات والرفض: customer_request_assignments بدل نصوص ملصقة في notes.
 * - #4 حالات العملاء enum موحّد (بدون قيم مفتوحة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('user_id', 36)->nullable();           // صاحب الطلب (null لـ guest)
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('full_name', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->enum('customer_type', ['guest', 'need_service']);
            $table->string('governorate', 255)->nullable();
            $table->string('service', 255)->nullable();        // من قوائم الخدمات الثابتة
            $table->text('notes')->nullable();                 // ملاحظات حرة (التاريخ في جدول الـ history)
            $table->char('assigned_provider_id', 36)->nullable();
            $table->string('assigned_provider_name', 255)->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->enum('status', [
                'pending', 'active', 'assigned', 'approved', 'in_progress',
                'completed', 'cancelled', 'refused', 'deleted',
            ])->default('pending');
            $table->enum('customer_response', ['approved', 'refused'])->nullable();
            $table->text('customer_reason')->nullable();
            $table->unsignedSmallInteger('whats_count')->default(0);
            $table->unsignedSmallInteger('calls_count')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_provider_id');
            $table->index('created_at');
        });

        // سجل الترشيحات/الرفض — بديل نصوص [رفض سابق: …] (قرار #7).
        Schema::create('customer_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->char('customer_id', 36);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->char('provider_id', 36)->nullable();
            $table->string('provider_name', 255)->nullable();  // الاسم وقت التعيين
            $table->char('acted_by', 36)->nullable();          // الأدمن/المكتب الذي نفّذ الإجراء
            $table->enum('action', ['assigned', 'reassigned', 'refused', 'completed', 'cancelled']);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('provider_id');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);                      // نسخة cached من الهدف
            $table->string('image', 500)->nullable();
            $table->enum('type', ['engineer', 'office', 'exhibition']);
            $table->string('slug', 255);
            $table->string('phone', 20)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('rating', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255);                      // slug الهدف أو "t3meer"
            // نوع الهدف في تقييم الملف، ونوع الكاتب في تقييم المنصة (دوال مغلقة).
            $table->enum('type', ['customer', 'engineer', 'office', 'exhibition'])->nullable();
            $table->char('user_id', 36)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('name', 255);                      // اسم الكاتب
            $table->unsignedTinyInteger('rating')->between(1, 5);
            $table->text('message')->nullable();
            $table->string('image', 500)->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->unique(['user_id', 'slug']);              // تقييم واحد لكل (مستخدم, هدف)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('customer_request_assignments');
        Schema::dropIfExists('customers');
    }
};
