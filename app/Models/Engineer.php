<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ملف المهندس (SPEC 1.2.2) — id = uuid كما في بيانات البروداكشن.
 * skills: JSON محوّل من text[] (لا lookup، لا trigger).
 */
class Engineer extends Model
{
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
