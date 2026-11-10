<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * عدادات التحليلات (SPEC 1.2.18).
 *
 * - الكتابة من Laravel فقط (upsert atomic) — بديل RPC increment_analytics.
 *   ⛔ ممنوع stored procedures/triggers (SPEC 0.5, 1.4).
 * - UNIQUE(stat_type, stat_key): page/home, page/engineers, service/X, ...
 * - stat_type: page | service | governorate | sort (whitelist — SPEC 5.3.6).
 */
class AnalyticsCount extends Model
{
    protected $table = 'analytics_counts';

    /** الأنواع المسموحة (SPEC 5.3.6). */
    public const STAT_TYPES = ['page', 'service', 'governorate', 'sort'];

    protected $fillable = [
        'id', 'stat_type', 'stat_key', 'total_count',
    ];

    protected function casts(): array
    {
        return [
            'total_count' => 'integer',
        ];
    }
}
