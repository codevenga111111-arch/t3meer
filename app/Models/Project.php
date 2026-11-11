<?php

namespace App\Models;

use App\Enums\PlanType;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * إعادة بناء `premium_active_view` (SPEC 1.3 + 5.2.5 + مهمة ClickUp #8).
     *
     * مشاريع أصحاب الباقات المميزة والحسابات النشطة فقط، الأحدث أولاً
     * (الview الأصلي كان order by created_at desc).
     *
     * ⚠ شرط "الباقات النشطة" مُستنتج (SPEC 5.2.5 — تعريف الـ view الأصلي
     * مش موجود في الريبو): users.selected_plan = premium + users.status = active.
     * بدون فحص subscriptions عشان ما نخسرش حسابات الباقة المدفوعة يدوياً.
     */
    public function scopePremiumActiveView(Builder $query): Builder
    {
        return $query->select('projects.*')
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->where('users.status', Status::Active->value)
            ->where('users.selected_plan', PlanType::Premium->value)
            ->orderByDesc('projects.created_at');
    }

    /**
     * آخر $window مشروع ثم واحد لكل مستخدم بحد $take (SPEC 1.7.6 + 5.2.5):
     * الرئيسية بتاخد آخر 100 → أول مشروع لكل user_id (الأحدث) → 15 كحد أقصى.
     *
     * يُستدعى على ناتج premiumActiveView():
     * Project::premiumActiveView()->onePerUser()->get()
     *
     * التنفيذ: ROW_NUMBER() OVER (PARTITION BY user_id) على نسخة محدودة بـ
     * $window صف، ثم rn = 1 على الـ derived table (MySQL 8 + SQLite 3.25+).
     */
    public function scopeOnePerUser(Builder $query, int $take = 15, int $window = 100): Builder
    {
        $inner = $query->getQuery()
            ->select('projects.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY projects.user_id ORDER BY projects.created_at DESC) as row_num')
            ->limit(max(1, $window));

        return $query->getModel()->newQuery()
            ->fromSub($inner, 'projects')
            ->where('row_num', 1)
            ->orderByDesc('projects.created_at')
            ->limit(max(1, $take));
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
