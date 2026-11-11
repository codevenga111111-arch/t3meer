<?php

namespace App\Models;

use App\Traits\PublicViewScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** ملف المكتب الهندسي (SPEC 1.2.3) — id = uuid كما في بيانات البروداكشن. */
class Office extends Model
{
    use PublicViewScope;

    protected $table = 'offices';

    protected $fillable = [
        'id', 'user_id', 'name', 'slug', 'governorate', 'bio', 'whats_app',
        'experience_years', 'count_of_projects', 'open_hours', 'latitude',
        'longitude', 'profile_image', 'video', 'facebook', 'instagram', 'youtube',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'experience_years' => 'integer',
            'count_of_projects' => 'integer',
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
     * إعادة بناء `public_offices_view` (SPEC 1.3 + مهمة ClickUp #8).
     *
     * الحسابات النشطة فقط + phone/email/selected_plan من users +
     * average_rating/reviews_count/total_visits + services/projects/permissions
     * كـ JSON في الـ select.
     *
     * الترتيب الافتراضي للدليل: ->orderByPlanWeight() (SPEC 1.8.12).
     * الفلاتر (search/governorate/category) بتيجي مع الـ endpoints.
     */
    public function scopePublicOfficesView(Builder $query): Builder
    {
        $query->select('offices.*');

        $this->joinActiveUsers($query);
        $this->withViewAggregates($query);
        $this->withViewCollections($query, ['services', 'projects', 'permissions']);

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
