<?php

namespace App\Services;

use App\Enums\MetricType;
use App\Models\Stat;
use Carbon\CarbonInterface;

/**
 * قراءات الإحصائيات — بديلَي RPC `get_limited_stats(p_slug)` و`get_all_stats(p_slug)`
 * (SPEC 1.4 + 3.3.2 + القرار المُحسم في 1.10.3 + مهمة ClickUp #9).
 *
 * الفروق عن الـ RPC القديمة (مصححة هنا):
 * - `get_limited_stats` = الفترة الحالية (الشهر التقويمي — انظر
 *   `Stat::scopeCurrentMonth`) والقيمة = **SUM(count)** لكل metric.
 * - `get_all_stats` القديمة كانت ترجع صفوف {metric_type, created_at} والواجهة
 *   تعدّ **عدد الصفوف** لكل شهر — مع grain الجديد (صف لكل يوم) ده كان هيرجّع
 *   0/1 لكل شهر (7.1#8). هنا: سلسلة الزيارات **مجمّعة بالشهر بـ SUM(count)**
 *   وبتتعوّض الشهور الفاضية بصفر (جاهزة للرسم).
 *
 * الاتنين query scopes على `Stat` (المطلوب في المهمة) — الخدمة مجرد غلاف
 * مريح لاستهلاك الداشبورد (يوم 7).
 */
final class StatsService
{
    /**
     * عدادات الفترة الحالية للملف — بديل `get_limited_stats`.
     *
     * @return array<string, int> الـ 8 metrics بنفس مفاتيح الـ RPC القديم
     *                            (visit, whatsapp, call, email, map, facebook,
     *                            instagram, youtube) — الغايب = 0.
     */
    public function limitedStats(string $slug): array
    {
        $sums = Stat::query()
            ->forSlug($slug)
            ->currentMonth()
            ->selectRaw('metric_type, SUM(count) as total')
            ->groupBy('metric_type')
            ->get();

        $result = array_fill_keys(MetricType::values(), 0);

        foreach ($sums as $row) {
            $raw = $row->getAttribute('metric_type');
            // الـ cast بيرجّع enum للصفوف العادية والـ selectRaw كمان — نطبّق عليه normalize.
            $metric = MetricType::normalizeAlias(
                $raw instanceof MetricType ? $raw->value : (string) $raw
            );
            $result[$metric] = (int) $row->getAttribute('total');
        }

        return $result;
    }

    /**
     * سلسلة الزيارات الشهرية — بديل `get_all_stats` لرسم الـ 12 شهر.
     *
     * @return list<array{month: string, count: int}> الأقدم أولاً، شهر واحد لـ
     *                                                كل شهر في النطاق (بما فيه
     *                                                الشهور بلا زيارات = 0).
     */
    public function visitsChart(string $slug, int $months = 12): array
    {
        $rows = Stat::query()
            ->forSlug($slug)
            ->visits()
            ->recentMonths($months)
            ->get(['date', 'count']);

        $bucket = [];

        foreach ($rows as $row) {
            $date = $row->getAttribute('date');
            $month = $date instanceof CarbonInterface
                ? $date->format('Y-m')
                : substr((string) $date, 0, 7);

            $bucket[$month] = ($bucket[$month] ?? 0) + (int) $row->getAttribute('count');
        }

        $series = [];
        $cursor = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $series[] = ['month' => $key, 'count' => $bucket[$key] ?? 0];
            $cursor = $cursor->addMonthNoOverflow();
        }

        return $series;
    }
}
