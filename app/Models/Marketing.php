<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * حملات التسويق (SPEC 1.2.19).
 *
 * - banner: JSON {title, description, image} — صورة من Cloudinary/banners.
 * - social: JSON {facebook, instagram, youtube, meta_id, caption, image}.
 * - meta_pixel: Pixel ID الخاص بالمشترك (fallback لو غير مفعّل بالأدمن).
 * - user_id UNIQUE (upsert على user_id).
 *
 * Enforcement للأقفال في SPEC 3.9 (banner/meta_pixel/social prohibited
 * حسب صلاحيات user_permissions).
 */
class Marketing extends Model
{
    protected $table = 'marketing';

    protected $fillable = [
        'user_id', 'banner', 'meta_pixel', 'social',
        'plan_type', 'user_type', 'slug',
    ];

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'banner' => 'array',
            'social' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
