<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** ملف المكتب الهندسي (SPEC 1.2.3) — id = uuid كما في بيانات البروداكشن. */
class Office extends Model
{
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
