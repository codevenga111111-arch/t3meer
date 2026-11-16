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

    /**
     * عدادات slug معين (بديل جزئي لـ RPC `get_limited_stats(p_slug)` — SPEC 1.4).
     */
    public function scopeForSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /** تصفية على نوع واحد من الـ metrics الثمانية. */
    public function scopeMetric($query, MetricType|string $metric)
    {
        $value = $metric instanceof MetricType ? $metric->value : MetricType::normalizeAlias((string) $metric);

        return $query->where('metric_type', $value);
    }

    /**
     * فترة محددة بالتاريخ (inclusive) — أساس `get_limited_stats`.
     *
     * ⚠ `whereDate` وليس `whereBetween`: الـ cast بيخزّن القيمة بصيغة
     * `Y-m-d H:i:s` في SQLite، ومقارنة النصوص هاستبعد صف اليوم الأخير
     * (`'2026-11-16 00:00:00' > '2026-11-16'`) — وده كان بيرجّع مجموع ناقص.
     */
    public function scopePeriod($query, string $from, string $to)
    {
        return $query->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);
    }

    /**
     * الفترة المعتمدة لـ `get_limited_stats` = **الشهر التقويمي الحالي**
     * (القرار المُحسم لـ SPEC 1.10.3: "تعريف الفترة غير معروف").
     *
     * المبرر: النص المعروض في الداشبورد "زيارات هذا الشهر" وبالأدمن
     * `.visit` = "زيارات الشهر الحالي" (admin.api.ts:631) — فالتقاطع الأول هو
     * الشهر، وإلا لكان الاسم limited بدل monthly.
     */
    public function scopeCurrentMonth($query)
    {
        // whereDate — نفس مبرر scopePeriod (آخر يوم في الشهر كان بيتسابت).
        return $query->whereDate('date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('date', '<=', now()->endOfMonth()->toDateString());
    }

    /**
     * آخر N شهر (بما فيها الشهر الحالي) — أساس رسم `get_all_stats` 12 شهراً.
     * الرسم بيجمع SUM(count) لكل شهر (قرار 7.1#8) وليس عدّ الصفوف.
     */
    public function scopeRecentMonths($query, int $months = 12)
    {
        return $query->where('date', '>=', now()->subMonthsNoOverflow($months - 1)->startOfMonth()->toDateString());
    }
}
