<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * إعادة بناء `marketing_active_users` (SPEC 1.3 + 5.2.10 + مهمة ClickUp #8).
     *
     * حملات التسويق لحسابات النشطة فقط وبانر موجود (الفلاتر التانية —
     * user_type وplan_type — بتضيفها العميل زي 5.2.10: مهندس = premium،
     * والباقي = professional).
     */
    public function scopeMarketingActiveUsers(Builder $query): Builder
    {
        return $query->select('marketing.*')
            ->join('users', 'users.id', '=', 'marketing.user_id')
            ->where('users.status', Status::Active->value)
            ->whereNotNull('marketing.banner');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
