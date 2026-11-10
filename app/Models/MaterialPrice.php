<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * أسعار مواد البناء (SPEC 1.2.20).
 *
 * - قراءتها في الصفحة الرئيسية (GET /api/v1/material-prices) +
 *   CRUD للأدمن (مهمة 35) — لازم توافق الأعمدة.
 * - change_percent ممكن يكون سالب (انخفاض) — decimal عادي مش unsigned.
 */
class MaterialPrice extends Model
{
    protected $table = 'materials_prices';

    protected $fillable = [
        'id', 'category', 'item_name', 'unit', 'price', 'change_percent',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float:2',
            'change_percent' => 'float:2',
        ];
    }
}
