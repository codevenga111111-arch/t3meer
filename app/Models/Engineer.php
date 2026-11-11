<?php

namespace App\Models;

use App\Traits\PublicViewScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ملف المهندس (SPEC 1.2.2) — id = uuid كما في بيانات البروداكشن.
 * skills: JSON محوّل من text[] (لا lookup، لا trigger).
 */
class Engineer extends Model
{
    use PublicViewScope;

    protected $table = 'engineers';

    protected $fillable = [
        'id', 'user_id', 'name', 'slug', 'phone', 'major', 'experience_years',
        'governorate', 'city', 'bio', 'whats_app', 'website', 'working_hours',
        'social', 'skills', 'latitude', 'longitude', 'profile_image',
        'cover_image', 'video_url', 'video', 'facebook', 'instagram', 'youtube',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'social' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'experience_years' => 'integer',
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
     * إعادة بناء `public_engineers_view` (SPEC 1.3 + مهمة ClickUp #8).
     *
     * زي view المكاتب بالظبط + `user_name` = users.name (الكود القديم كان
     * يقرأه في البحث وبقائمة الأدمن) وphone بأولوية الملف ثم المستخدم.
     *
     * الترتيب الافتراضي للدليل: ->orderByPlanWeight() (SPEC 1.8.12).
     */
    public function scopePublicEngineersView(Builder $query): Builder
    {
        $query->select('engineers.*');

        $this->joinActiveUsers(
            $query,
            phone: 'COALESCE(engineers.phone, users.phone)',
            withUserName: true
        );
        $this->withViewAggregates($query);
        $this->withViewCollections($query, ['services', 'projects', 'permissions']);

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
