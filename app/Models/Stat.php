<?php

namespace App\Models;

use App\Enums\MetricType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * عداد تفاعلات (SPEC 1.2.17 + قرار 7.1#8).
 *
 * Grain يومي: UNIQUE(slug, metric_type, date) — بدل صف واحد بتغيّر
 * created_at كل زيادة (كان يكسر الرسم البياني).
 * الزيادة = upsert +1 على العداد (atomic) — لا triggers (SPEC 1.4).
 *
 * - visit: dedupe مرة واحدة في اليوم لكل (slug, device_id|ip) — SPEC 5.3.5.
 * - باقي الـ metrics بدون dedupe.
 * - user_id = الزائر (آخر زائر فقط) وليس صاحب الملف.
 */
class Stat extends Model
{
    protected $table = 'stats';

    protected $fillable = [
        'id', 'slug', 'metric_type', 'date', 'count', 'user_id', 'type',
    ];

    protected function casts(): array
    {
        return [
            'metric_type' => MetricType::class,
            'date' => 'date',
            'count' => 'integer',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * مجموع الزيارات = SUM(count) حيث metric_type = visit
     * (⚠ ليس عدّ الصفوف — SPEC 7.1#8 و1.3).
     */
    public function scopeVisits($query)
    {
        return $query->where('metric_type', MetricType::Visit->value);
    }
}
