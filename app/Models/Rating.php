<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقييم (SPEC 1.2.16 + مهمة ClickUp #5).
 *
 * ⛔ العمود القديم `type` كان ذو دلالة مزدوجة:
 *   - تقييم ملف:   نوع الهدف
 *   - تقييم المنصة: نوع الكاتب
 * → فُصل إلى عمودين (migration 2026_11_11_000002):
 *
 *   target_type: نوع الهدف — engineer | office | exhibition | platform
 *                (platform ⇔ slug = "t3meer").
 *   writer_type: نوع الكاتب — customer | engineer | office | exhibition | admin.
 *
 * - UNIQUE(user_id, slug): تقييم واحد لكل (مستخدم, هدف) — يُفرض server-side
 *   ويرجع 422 "لقد قمت بتقييم هذا الملف مسبقاً" (SPEC 5.3.1).
 * - name/image من البروفايل لا من الـ body (SPEC 5.3.1).
 */
class Rating extends Model
{
    protected $table = 'rating';

    /** slug تقييمات المنصة (SPEC 1.2.16 + 5.3.1). */
    public const PLATFORM_SLUG = 't3meer';

    protected $fillable = [
        'id', 'slug', 'target_type', 'user_id', 'writer_type',
        'name', 'rating', 'message', 'image',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** هل هو تقييم للمنصة نفسها؟ */
    public function isPlatformReview(): bool
    {
        return $this->getAttribute('slug') === self::PLATFORM_SLUG;
    }

    /**
     * الهدف المسموح به حسب نوع التقييم (SPEC 5.3.1).
     * platform ⇔ slug = t3meer، وغيره يقبل engineer/office/exhibition.
     */
    public static function validTargetTypes(): array
    {
        return ['engineer', 'office', 'exhibition', 'platform'];
    }
}
