<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * صلاحيات المستخدم (SPEC 1.2.8) — قرار #6: حدود الباقات هنا.
 * صفوف template بـ user_id = NULL (من جدول plans) + صف واحد لكل مستخدم.
 */
class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = [
        'user_id', 'user_type', 'plan_type', 'max_visits', 'max_projects',
        'max_services', 'max_requests', 'whatsapp_tracking', 'call_tracking',
        'detailed_analytics', 'video_projects', 'google_maps_location',
        'directory_ad_banner', 'distinguished_badge', 'homepage_ad_banner',
        'social_media_promo', 'facebook_pixel', 'priority_search',
    ];

    protected function casts(): array
    {
        return [
            'max_visits' => 'integer',
            'max_projects' => 'integer',
            'max_services' => 'integer',
            'max_requests' => 'integer',
            'whatsapp_tracking' => 'boolean',
            'call_tracking' => 'boolean',
            'detailed_analytics' => 'boolean',
            'video_projects' => 'boolean',
            'google_maps_location' => 'boolean',
            'directory_ad_banner' => 'boolean',
            'distinguished_badge' => 'boolean',
            'homepage_ad_banner' => 'boolean',
            'social_media_promo' => 'boolean',
            'facebook_pixel' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** قالب الحدود لباقة (user_id = NULL). */
    public static function templateFor(string $userType, string $planType): ?self
    {
        return static::whereNull('user_id')
            ->where('user_type', $userType)
            ->where('plan_type', $planType)
            ->first();
    }
}
