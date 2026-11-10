<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * مفضلة (SPEC 1.2.15).
 *
 * - UNIQUE(user_id, slug) تُفرض server-side (لا client-side).
 * - name/image/phone = نسخة cached من الهدف — تُملأ من جدول الهدف
 *   server-side ولا تأتي من الـ body (SPEC 5.3.3).
 * - type: engineer | office | exhibition (المورد = exhibition — قرار 0.5).
 */
class Favorite extends Model
{
    protected $table = 'favorites';

    protected $fillable = [
        'id', 'user_id', 'name', 'image', 'type', 'slug', 'phone',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
