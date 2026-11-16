<?php

namespace App\Models;

use App\Enums\PlanType;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
     * ⚠ المقارنة على بيانات البروداكشن (المهمة 10) صحّحت فهمين كانوا غلط:
     * 1) الـ view القديم مش بيشترط وجود بانر — 14 صف من 24 صف فيه banner=null
     *    (العميل هو اللي بيضيف `.not("banner","is",null)` زي 5.2.10/5.2.1).
     *    شرط البانر هنا كان بيقصي 14 صف حقيقي.
     * 2) الشرط الحقيقي هو **اشتراك مدفوع نشط** (≠ free): كل صفوف الـ view
     *    لها اشتراك active وباقة ≠ free، ومفيش صف لأصحاب الاشتراك الحر.
     *
     * بنستخدم EXISTS عشان ما نضاعفش الصفوف لو اتوجد أكتر من اشتراك لنفس المستخدم.
     */
    public function scopeMarketingActiveUsers(Builder $query): Builder
    {
        return $query->select('marketing.*')
            ->join('users', 'users.id', '=', 'marketing.user_id')
            ->where('users.status', Status::Active->value)
            ->whereExists(function ($subscription): void {
                $subscription->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.user_id', 'marketing.user_id')
                    ->where('subscriptions.status', Status::Active->value)
                    ->where('subscriptions.plan_type', '!=', PlanType::Free->value);
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
