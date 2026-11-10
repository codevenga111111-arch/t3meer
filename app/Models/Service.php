<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * خدمة/تخصص (SPEC 1.2.6) — جدول واحد لثلاثة أغراض:
 * تخصص مهندس، تخصص مكتب، تصنيف منتجات فرعية للمعرض.
 *
 * - user_id ليس UNIQUE (عددة صفوف لكل مستخدم).
 * - slug = slug المالك (denormalized) — لا يُولَّد من اسم الخدمة.
 * - icon/color إلزاميان لعرض خدمات الكتالوج (SPEC 3.7).
 */
class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'user_id', 'name', 'icon', 'color', 'slug',
    ];

    protected function casts(): array
    {
        return [
            'color' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
