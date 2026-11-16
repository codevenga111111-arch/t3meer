<?php

/**
 * اختبارات استبدال الـ RPCs بمنطق Laravel — التتبع والإحصائيات
 * (SPEC 1.4 + 5.3.6 + 7.1#8 + مهمة ClickUp #9).
 *
 * بيغطي:
 * - `increment_analytics(p_stat_type, p_stat_key)` → AnalyticsService
 *   (upsert atomic + whitelists + خريطة materials→showrooms-suppliers)
 * - `get_limited_stats(p_slug)` / `get_all_stats(p_slug)` → query scopes على
 *   `Stat` + StatsService (الفترة = الشهر الحالي، والرسم = SUM(count) شهرياً)
 *
 * بيئة التشغيل: SQLite in-memory (phpunit.xml) = بيئة CI.
 */

use App\Models\AnalyticsCount;
use App\Models\Stat;
use App\Services\AnalyticsService;
use App\Services\StatsService;
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------------------
// increment_analytics → AnalyticsService (upsert atomic)
// ---------------------------------------------------------------------------

it('creates the counter row on the first increment', function () {
    $total = (new AnalyticsService)->increment('page', 'home');

    expect($total)->toBe(1)
        ->and(AnalyticsCount::query()->count())->toBe(1);
});

it('increments atomically without a read-then-write race', function () {
    $service = new AnalyticsService;

    $service->increment('page', 'home');
    $service->increment('page', 'home');
    $total = $service->increment('page', 'home');

    expect($total)->toBe(3)
        ->and(AnalyticsCount::query()->where('stat_key', 'home')->value('total_count'))->toBe(3)
        ->and(DB::table('analytics_counts')->count())->toBe(1); // صف واحد للزوج (1.4)
});

it('rejects unknown stat types and keys with arabic messages', function () {
    $service = new AnalyticsService;

    expect(fn () => $service->increment('secret', 'home'))
        ->toThrow(InvalidArgumentException::class, 'نوع الإحصائية غير صالح');

    expect(fn () => $service->increment('page', 'admin-panel'))
        ->toThrow(InvalidArgumentException::class, 'قيمة الإحصائية غير معروفة');

    expect(fn () => $service->increment('sort', 'random'))
        ->toThrow(InvalidArgumentException::class, 'قيمة الإحصائية غير معروفة');

    expect(fn () => $service->increment('page', '   '))
        ->toThrow(InvalidArgumentException::class, 'مفتاح الإحصائية مطلوب');
});

it('merges the legacy materials page key into showrooms-suppliers (0.5)', function () {
    $service = new AnalyticsService;

    $service->increment('page', 'showrooms-suppliers');
    $service->increment('page', 'materials'); // الدليل القديم للموردين

    expect(AnalyticsCount::query()->where('stat_key', 'materials')->exists())->toBeFalse()
        ->and(AnalyticsCount::query()->where('stat_key', 'showrooms-suppliers')->value('total_count'))->toBe(2);
});

it('accepts directory sort values and free text filters', function () {
    $service = new AnalyticsService;

    $service->increment('sort', 'nearMe');
    $service->increment('governorate', '  القاهرة ');
    $service->increment('service', 'تشطيبات');

    expect(AnalyticsCount::query()->count())->toBe(3)
        ->and(AnalyticsCount::query()->where('stat_key', 'القاهرة')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// get_limited_stats / get_all_stats → Stat scopes + StatsService
// ---------------------------------------------------------------------------

/** صف إحصائيات بـ slug وتاريخ معيّنين. */
function rpcStat(string $slug, string $metric, string $date, int $count): Stat
{
    return Stat::create([
        'slug' => $slug,
        'metric_type' => $metric,
        'date' => $date,
        'count' => $count,
    ]);
}

it('scopes stats by slug, metric and period', function () {
    rpcStat('office-a', 'visit', now()->toDateString(), 5);
    rpcStat('office-a', 'whatsapp', now()->toDateString(), 2);
    rpcStat('office-b', 'visit', now()->toDateString(), 99);
    rpcStat('office-a', 'visit', now()->subMonth()->toDateString(), 40); // الشهر اللي فات

    $currentMonthVisits = Stat::query()
        ->forSlug('office-a')
        ->visits()
        ->currentMonth()
        ->sum('count');

    expect($currentMonthVisits)->toBe(5)
        ->and(Stat::query()->forSlug('office-a')->count())->toBe(3)
        ->and(Stat::query()->forSlug('office-a')->metric('call')->count())->toBe(0)
        ->and(Stat::query()->forSlug('office-a')->metric('whatsapp')->count())->toBe(1)
        ->and(Stat::query()->forSlug('office-a')->period(
            now()->subMonth()->toDateString(),
            now()->toDateString()
        )->sum('count'))->toBe(47);
});

it('returns the eight dashboard counters for the current month only', function () {
    rpcStat('office-a', 'visit', now()->toDateString(), 10);
    // نفس الشهر بس يوم تاني — الـ grain يومي UNIQUE(slug, metric_type, date).
    $otherDay = now()->day === 1
        ? now()->addDay()->toDateString()
        : now()->startOfMonth()->toDateString();
    rpcStat('office-a', 'visit', $otherDay, 4);
    rpcStat('office-a', 'whatsapp', now()->toDateString(), 3);
    rpcStat('office-a', 'call', now()->subMonth()->toDateString(), 77); // شهر تاني = تتعرف

    $stats = (new StatsService)->limitedStats('office-a');

    expect($stats)->toHaveKeys(['visit', 'whatsapp', 'call', 'email', 'map', 'facebook', 'instagram', 'youtube'])
        ->and($stats['visit'])->toBe(14)
        ->and($stats['whatsapp'])->toBe(3)
        ->and($stats['call'])->toBe(0)
        ->and($stats['email'])->toBe(0);
});

it('returns zeroed counters when the profile has no stats', function () {
    $stats = (new StatsService)->limitedStats('no-such-slug');

    expect($stats)->toBe([
        'visit' => 0, 'whatsapp' => 0, 'call' => 0, 'email' => 0,
        'map' => 0, 'facebook' => 0, 'instagram' => 0, 'youtube' => 0,
    ]);
});

it('builds the monthly visits chart by summing counts instead of rows', function () {
    // الشهر الحالي: صفوف متعددة — القديم كان يعدّ الصفوف فيرجع 2 بدل 7 (7.1#8).
    rpcStat('office-a', 'visit', now()->startOfMonth()->addDays(2)->toDateString(), 3);
    rpcStat('office-a', 'visit', now()->startOfMonth()->addDays(9)->toDateString(), 4);
    // شهر قبل كده + metrics تانية وصفوف برّة النطاق.
    rpcStat('office-a', 'visit', now()->subMonthsNoOverflow(2)->startOfMonth()->addDays(3)->toDateString(), 5);
    rpcStat('office-a', 'whatsapp', now()->toDateString(), 99);
    rpcStat('office-a', 'visit', now()->subMonthsNoOverflow(15)->toDateString(), 1000);

    $chart = (new StatsService)->visitsChart('office-a', 12);

    expect($chart)->toHaveCount(12)
        ->and($chart[11]['month'])->toBe(now()->format('Y-m'))
        ->and($chart[11]['count'])->toBe(7)
        ->and($chart[9]['month'])->toBe(now()->subMonthsNoOverflow(2)->format('Y-m'))
        ->and($chart[9]['count'])->toBe(5)
        ->and(array_column($chart, 'month'))->toBe(array_values(array_unique(array_column($chart, 'month'))))
        ->and(collect($chart)->sum('count'))->toBe(12); // 7 + 5 فقط
});
