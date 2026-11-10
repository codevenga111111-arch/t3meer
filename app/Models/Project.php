<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * مشروع (معرض الأعمال) — SPEC 1.2.7.
 *
 * - id = CHAR(36) UUID يولّده السيرفر.
 * - slug = slug المالك (denormalized) — الزيارة تُسجَّل على slug المالك.
 * - details = JSON {video, brief, categories[], photos[]} (كان jsonb).
 * - user_type/user_name يملأها السيرفر — ممنوع إرسالها من body (SPEC 0.4).
 */
class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'id', 'user_id', 'name', 'location', 'area', 'type', 'image',
        'slug', 'user_type', 'user_name', 'views', 'details',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'area' => 'float',
            'views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->getAttribute('id')) {
                $model->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * هل يحوي تفاصيل تُظهر صفحة مخصصة (أو نكتفي بصفحة المالك)؟ (SPEC 5.2.20).
     */
    public function hasDetails(): bool
    {
        $details = (array) $this->getAttribute('details');

        foreach (['brief', 'video', 'photos'] as $key) {
            $value = $details[$key] ?? null;

            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }
}
