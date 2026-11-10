<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * منتج (للمعرض/المورد) — SPEC 1.2.8.
 *
 * - سعر + تصنيف رئيسي (23 فئة) + تصنيف فرعي نص حر.
 * - details = JSON {description, photos[]} (كان jsonb).
 * - حد الباقة يستخدم عمود max_projects (لا يوجد max_products) — SPEC 3.6.
 */
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'id', 'user_id', 'name', 'category', 'sub_category',
        'image', 'price', 'slug', 'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'price' => 'float:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
