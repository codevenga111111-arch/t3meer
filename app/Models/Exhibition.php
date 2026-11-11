<?php

namespace App\Models;

use App\Traits\PublicViewScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ملف المعرض/المورد (SPEC 1.2.4).
 * قرار #2: المورد = exhibition (material/supplier اتحوّلوا وقت الترحيل).
 * brands: JSON محوّل من text[].
 */
class Exhibition extends Model
{
    use PublicViewScope;

    protected $table = 'exhibitions';

    protected $fillable = [
        'id', 'user_id', 'name', 'slug', 'main_category', 'governorate', 'city',
        'bio', 'whats_app', 'experience_years', 'latitude', 'longitude', 'logo',
        'banner', 'profile_image', 'count_of_categories', 'open_hours', 'video',
        'brands', 'facebook', 'instagram', 'youtube',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'brands' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'experience_years' => 'integer',
            'count_of_categories' => 'integer',
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
     * إعادة بناء `public_exhibitions_view` (SPEC 1.3 + مهمة ClickUp #8).
     *
     * ⛔ بديل `public_materials_view` (القرار 0.5: المورد = exhibition).
     * زي view المكاتب + products (المنتجات) وcount_of_projects (في الـ Resource
     * بيتحسب من مصفوفة projects لأن المعرض ما عندهش عمود count_of_projects).
     *
     * الترتيب الافتراضي للدليل: ->orderByPlanWeight() (SPEC 1.8.12).
     */
    public function scopePublicExhibitionsView(Builder $query): Builder
    {
        $query->select('exhibitions.*');

        $this->joinActiveUsers($query);
        $this->withViewAggregates($query);
        $this->withViewCollections($query, ['services', 'projects', 'products', 'permissions']);

        return $query;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
