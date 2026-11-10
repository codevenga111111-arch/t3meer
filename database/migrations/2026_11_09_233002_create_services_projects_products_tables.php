<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * services + projects + products (SPEC 1.2.6–1.2.8).
 *
 * - services: جدول واحد لتخصصات المهندس/المكتب وتصنيفات المنتجات الفرعية.
 * - projects.id: uuid CHAR(36) يولّده السيرفر (كان uuid في Postgres).
 * - details: jsonb → JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36);                       // ليس unique (عدة خدمات لكل مستخدم)
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('icon', 100)->nullable();           // Iconify id
            $table->string('color', 20)->default('#0070d7');   // hex
            $table->string('slug', 255);                       // slug المالك (denormalized)
            $table->timestamps();

            $table->index('user_id');
            $table->index('slug');
            $table->index('name');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->char('id', 36)->primary();                 // uuid يولّده السيرفر
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('location', 255)->nullable();
            $table->decimal('area', 12, 2)->nullable();
            $table->string('type', 100)->nullable();           // نوع العقار/المشروع
            $table->string('image', 500)->nullable();
            $table->string('slug', 255);                       // slug المالك للتتبع
            $table->string('user_type', 20)->nullable();       // denormalized
            $table->string('user_name', 255)->nullable();      // denormalized
            $table->unsignedInteger('views')->default(0);
            $table->json('details')->nullable();               // {video, brief, categories[], photos[]}
            $table->timestamps();

            $table->index('user_id');
            $table->index('slug');
            $table->index('created_at');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('category', 255)->nullable();
            $table->string('sub_category', 255)->nullable();
            $table->string('image', 500)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('slug', 255);                       // slug المالك
            $table->json('details')->nullable();               // {description, photos[]}
            $table->timestamps();

            $table->index('user_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('products');
    }
};
