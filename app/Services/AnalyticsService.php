<?php

namespace App\Services;

use App\Models\AnalyticsCount;
use Illuminate\Support\Facades\DB;

/**
 * عدادات التحليلات — بديل RPC `increment_analytics(p_stat_type, p_stat_key)`
 * (SPEC 1.4 + 5.3.6 + الإصلاح 1.9.13 + مهمة ClickUp #9).
 *
 * الـ RPC القديم كان مستدعى **من المتصفح مباشرة** بالـ anon key مع race
 * condition (read-then-write) وأي حد يقدر يكتب أرقام. هنا:
 * - الكتابة من السيرفر فقط (endpoint يوم 15: `POST /api/v1/track/analytics`).
 * - **upsert atomic** في استعلام واحد (`ON DUPLICATE KEY UPDATE` /
 *   `ON CONFLICT ... DO UPDATE`) — لا transaction ولا قراءة قبل الكتابة.
 * - whitelist لـ `stat_type` و `page`/`sort` (المفاتيح محدودة عددياً في 5.3.6)؛
 *   `service`/`governorate` نصوص من قوائم الاختيارات فتُقبل بطول محدود.
 * - خريطة 0.5: `page = materials` → `showrooms-suppliers` (المورد = معرض).
 */
final class AnalyticsService
{
    /** صفحات الدليل والرئيسية (SPEC 5.3.6). */
    public const PAGES = ['home', 'engineers', 'offices', 'exhibitions', 'showrooms-suppliers'];

    /** قيم ترتيب الدليل (SPEC 5.3.6 + 5.2.0). */
    public const SORTS = ['nearMe', 'experience', 'bestViewers', 'bestRating'];

    /** مفاتيح قديمة تُدمَج وقت الكتابة (قرار 0.5). */
    public const KEY_ALIASES = [
        'page' => ['materials' => 'showrooms-suppliers'],
    ];

    /**
     * زيادة العداد لـ (stat_type, stat_key) مع إنشاء الصف لو مش موجود.
     *
     * @return int القيمة الجديدة بعد الزيادة
     *
     * @throws \InvalidArgumentException برسالة عربية لو النوع/المفتاح خارج الـ whitelist
     */
    public function increment(string $statType, string $statKey): int
    {
        $statType = strtolower(trim($statType));

        if (! in_array($statType, AnalyticsCount::STAT_TYPES, true)) {
            throw new \InvalidArgumentException('نوع الإحصائية غير صالح');
        }

        $statKey = $this->normalizeKey($statType, $statKey);
        $this->upsert($statType, $statKey);

        return (int) AnalyticsCount::query()
            ->where('stat_type', $statType)
            ->where('stat_key', $statKey)
            ->value('total_count');
    }

    /** تطبيع المفتاح + الـ whitelist حسب النوع. */
    private function normalizeKey(string $statType, string $statKey): string
    {
        $statKey = trim($statKey);

        if ($statKey === '') {
            throw new \InvalidArgumentException('مفتاح الإحصائية مطلوب');
        }

        if (mb_strlen($statKey) > 255) {
            throw new \InvalidArgumentException('مفتاح الإحصائية طويل جداً');
        }

        $aliases = self::KEY_ALIASES[$statType] ?? [];
        $statKey = $aliases[$statKey] ?? $statKey;

        $allowed = match ($statType) {
            'page' => self::PAGES,
            'sort' => self::SORTS,
            default => null, // service / governorate: نصوص من قوائم الاختيارات.
        };

        if ($allowed !== null && ! in_array($statKey, $allowed, true)) {
            throw new \InvalidArgumentException('قيمة الإحصائية غير معروفة');
        }

        return $statKey;
    }

    /**
     * upsert atomic للعدّاد — استعلام واحد بلا قراءة مسبقة.
     * (MySQL: ON DUPLICATE KEY — SQLite: ON CONFLICT — الفهرس الفريد (stat_type, stat_key).
     */
    private function upsert(string $statType, string $statKey): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                'INSERT INTO analytics_counts (stat_type, stat_key, total_count, created_at, updated_at)
                 VALUES (?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE total_count = total_count + 1, updated_at = ?',
                [$statType, $statKey, $now, $now, $now]
            );

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement(
                'INSERT INTO analytics_counts (stat_type, stat_key, total_count, created_at, updated_at)
                 VALUES (?, ?, 1, ?, ?)
                 ON CONFLICT (stat_type, stat_key) DO UPDATE SET
                     total_count = total_count + 1,
                     updated_at = excluded.updated_at',
                [$statType, $statKey, $now, $now]
            );

            return;
        }

        // fallback لأي driver تاني (مثلاً pgsql): upsert + زيادة داخل transaction.
        DB::transaction(static function () use ($statType, $statKey): void {
            $affected = AnalyticsCount::query()->where([
                'stat_type' => $statType,
                'stat_key' => $statKey,
            ])->update(['total_count' => DB::raw('total_count + 1')]);

            if ($affected === 0) {
                AnalyticsCount::query()->create([
                    'stat_type' => $statType,
                    'stat_key' => $statKey,
                    'total_count' => 1,
                ]);
            }
        });
    }
}
