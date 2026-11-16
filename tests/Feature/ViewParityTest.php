<?php

/**
 * اختبار تكافؤ query scopes مع الـ views القديمة على بيانات البروداكشن الفعلية
 * (T3MER_CLICKUP_PLAN.md — اليوم 3، التاسكة 10: «مقارنة نتائج الـ scopes
 * بالـ views القديمة على بيانات حقيقية»).
 *
 * الطريقة:
 * 1) استيراد Supabase/*_rows.sql بأمر `t3meer:import-supabase` نفسه.
 * 2) تحميل ملف الـ view القديم (Supabase/<view>_rows.sql) كما هو داخل جدول
 *    مؤقت — القاعدة هي اللي تحلّل الـ INSERT (نفس معنى الملف، بلا محلل مكرر).
 * 3) مقارنة صف بصف بين الجدول القديم و scope الجديد، وتجميع كل فروق التوافق
 *    في رسالة واحدة (أي فرق = فشل الاختبار بوصفه).
 *
 * المرجع: T3MEER_BACKEND_API_SPEC_v3.md 1.3 (الـ views وإعادة بنائها).
 */

use App\Models\Marketing;
use App\Models\Office;
use App\Models\Project;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// تحميل الـ views القديمة
// ---------------------------------------------------------------------------

/**
 * تنفيذ ملف تصدير view القديم داخل جدول مؤقت (`legacy_<view>`) وإرجاع صفوفه.
 *
 * الجدول كله أعمدة TEXT — التطبيع بيحصل في المقارنة (أرقام/بوليانات/JSON).
 *
 * @return Collection<int, object>
 */
function parityLegacyView(string $view): Collection
{
    $file = base_path("Supabase/{$view}_rows.sql");
    $sql = ltrim((string) file_get_contents($file), "\xEF\xBB\xBF");
    $pattern = '/INSERT\s+INTO\s+public\.'.preg_quote($view, '/').'\s*\(([^)]*)\)/i';

    if (! preg_match($pattern, $sql, $matches)) {
        throw new RuntimeException("تعذّر قراءة أعمدة الـ INSERT في {$file}");
    }

    $columns = array_map('trim', explode(',', $matches[1]));
    $table = "legacy_{$view}";

    Schema::create($table, function (Blueprint $blueprint) use ($columns) {
        foreach ($columns as $column) {
            $blueprint->string($column)->nullable();
        }
    });

    $sql = preg_replace($pattern, "INSERT INTO {$table}", $sql, 1);
    DB::statement(rtrim(trim($sql), "; \t\n\r\0\x0B"));

    return DB::table($table)->get();
}

/** تنسيق أي قيمة لرسالة عدم التوافق. */
function parityDump(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
}

/** JSON كانوني (فك + ترتيب المفاتيح + إعادة ترميز) للمقارنة. */
function parityJson(mixed $raw): ?string
{
    if ($raw === null || $raw === '') {
        return null;
    }

    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

    if (! is_array($decoded)) {
        return null;
    }

    $sort = function (array $items) use (&$sort): array {
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $items[$key] = $sort($value);
            }
        }

        if (! array_is_list($items)) {
            ksort($items);
        }

        return $items;
    };

    return json_encode($sort($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * permissions: الـ view القديم كان بيعرض 4 مفاتيح بس من كائن الصلاحيات
 * (`google_maps_location`, `distinguished_badge`, `video_projects`, `max_visits`)،
 * والـ scope بيبني الكائن كامل — فنقارن **تقاطع المفاتيح** اللي الـ view بيظهره
 * بعد توحيد البوليانات (SQLite يرجّع 0/1 و Postgres كان يرجّع true/false).
 *
 * مقارنة الكائن كامل كانت بتطلّع فروق وهمية لكل مفتاح مش موجود في الـ view.
 */
function parityPermissions(mixed $legacyRaw, mixed $scopeRaw): bool
{
    $legacy = is_string($legacyRaw) ? json_decode($legacyRaw, true) : $legacyRaw;
    $scope = is_string($scopeRaw) ? json_decode($scopeRaw, true) : $scopeRaw;

    if (! is_array($legacy) || ! is_array($scope)) {
        return parityJson($legacyRaw) === null && parityJson($scopeRaw) === null;
    }

    $flags = [
        'whatsapp_tracking', 'call_tracking', 'detailed_analytics', 'video_projects',
        'google_maps_location', 'directory_ad_banner', 'distinguished_badge',
        'homepage_ad_banner', 'social_media_promo', 'facebook_pixel',
    ];

    $normalize = static function (mixed $value, string $key) use ($flags): mixed {
        if (! in_array($key, $flags, true) || $value === null) {
            return $value;
        }

        return (bool) $value;
    };

    $expected = [];
    $actual = [];

    foreach ($legacy as $key => $value) {
        $expected[$key] = $normalize($value, (string) $key);
        $actual[$key] = $normalize($scope[$key] ?? null, (string) $key);
    }

    ksort($expected);
    ksort($actual);

    return parityJson($expected) === parityJson($actual);
}

/** services: القديم فيها slug إضافي — نقارن الاسم ← (أيقونة، لون). */
function parityServices(mixed $raw): array
{
    $items = is_string($raw) ? json_decode($raw, true) : $raw;

    if (! is_array($items)) {
        return [];
    }

    $map = [];

    foreach ($items as $item) {
        $map[$item['name'] ?? '?'] = [
            'icon' => $item['icon'] ?? null,
            'color' => $item['color'] ?? null,
        ];
    }

    ksort($map);

    return $map;
}

/** projects: نقارن العدد ثم المعرّفات. */
function parityProjects(mixed $raw): array
{
    $items = is_string($raw) ? json_decode($raw, true) : $raw;

    if (! is_array($items)) {
        return ['count' => 0, 'ids' => []];
    }

    $ids = array_values(array_filter(array_map(
        static fn (array $item): mixed => $item['id'] ?? null,
        $items
    )));
    sort($ids);

    return ['count' => count($items), 'ids' => $ids];
}

// ---------------------------------------------------------------------------
// المقارنة
// ---------------------------------------------------------------------------

/**
 * مقارنة صف بصف بين ملف الـ view القديم و scope الجديد.
 *
 * @param  string  $key  العمود المشترك اللي بتتربط به الصفوف (slug / id).
 * @param  array<int, string>  $jsonFields  أعمدة JSON عادية (banner, details…).
 * @param  array<int, string>  $skip  أعمدة القديم خارج نطاق الـ scope (created_at إلخ).
 * @param  list<string>  $mismatches  يُملأ بفروق التوافق.
 */
function parityCompare(
    string $key,
    Collection $legacy,
    Collection $scope,
    array &$mismatches,
    array $jsonFields = [],
    array $skip = [],
): void {
    foreach (array_diff($legacy->keys()->all(), $scope->keys()->all()) as $missing) {
        $mismatches[] = "مفقود من scope: {$key}={$missing}";
    }

    foreach (array_diff($scope->keys()->all(), $legacy->keys()->all()) as $extra) {
        $mismatches[] = "زيادة في scope (مش في الـ view القديم): {$key}={$extra}";
    }

    foreach ($legacy as $identifier => $row) {
        $target = $scope->get($identifier);

        if ($target === null) {
            continue;
        }

        foreach (get_object_vars($row) as $column => $legacyValue) {
            if (in_array($column, $skip, true) || ! array_key_exists($column, $target->getAttributes())) {
                continue; // العمود خارج نطاق الـ scope (أو مُقارَن يدويًا تحت).
            }

            $scopeValue = $target->getAttribute($column);

            $matched = match (true) {
                $column === 'permissions' => parityPermissions($legacyValue, $scopeValue),
                $column === 'services' => parityServices($legacyValue) === parityServices($scopeValue),
                $column === 'projects' => parityProjects($legacyValue) === parityProjects($scopeValue),
                in_array($column, $jsonFields, true) => parityJson($legacyValue) === parityJson($scopeValue),
                is_array($legacyValue) || is_array($scopeValue) => parityJson($legacyValue) === parityJson($scopeValue),
                is_numeric($legacyValue) && is_numeric($scopeValue) => abs((float) $legacyValue - (float) $scopeValue) <= max(1e-6, abs((float) $legacyValue) * 1e-9),
                default => (string) ($legacyValue ?? '') === (string) ($scopeValue ?? ''),
            };

            if (! $matched) {
                $mismatches[] = "{$key}={$identifier} · {$column}: "
                    .'legacy='.parityDump($legacyValue).' ≠ scope='.parityDump($scopeValue);
            }
        }
    }
}

beforeEach(function () {
    // نفس أمر الاستيراد الحقيقي — الملفات المفحوصة هي Supabase/*_rows.sql.
    expect(Artisan::call('t3meer:import-supabase', ['--path' => 'Supabase']))->toBe(0);
});

// ---------------------------------------------------------------------------
// الاختبارات
// ---------------------------------------------------------------------------

it('rebuilds public_offices_view from the real production dump', function () {
    $legacy = parityLegacyView('public_offices_view')->keyBy('slug');
    $scope = Office::publicOfficesView()->get()->keyBy('slug');
    $names = DB::table('users')->pluck('name', 'slug');
    $mismatches = [];

    // الـ view مقيّد بالحسابات النشطة فقط (SPEC 1.3).
    if ($legacy->pluck('status')->unique()->all() !== ['active']) {
        $mismatches[] = 'الـ view القديم فيه حسابات غير نشطة: '
            .parityDump($legacy->pluck('status')->unique()->all());
    }

    // office_name = اسم الملف، name = اسم الحساب (users.name).
    foreach ($legacy as $slug => $row) {
        if ((string) $row->office_name !== (string) ($scope->get($slug)?->name)) {
            $mismatches[] = "slug={$slug} · office_name ≠ scope.name: "
                .parityDump($row->office_name).' ≠ '.parityDump($scope->get($slug)?->name);
        }

        if ((string) $row->name !== (string) $names->get($slug)) {
            $mismatches[] = "slug={$slug} · name ≠ users.name: "
                .parityDump($row->name).' ≠ '.parityDump($names->get($slug));
        }
    }

    parityCompare(
        'slug',
        $legacy,
        $scope,
        $mismatches,
        jsonFields: ['permissions'],
        skip: ['created_at', 'updated_at', 'status', 'office_name', 'name'],
    );

    expect($legacy)->toHaveCount(20)   // ملف التصدير فيه 20 مكتب فعليًا.
        ->and($mismatches)->toBe([]);
});

it('rebuilds marketing_active_users from the real production dump', function () {
    $legacy = parityLegacyView('marketing_active_users')->keyBy('slug');
    $scope = Marketing::marketingActiveUsers()->get()->keyBy('slug');
    $mismatches = [];

    parityCompare(
        'slug',
        $legacy,
        $scope,
        $mismatches,
        jsonFields: ['banner', 'social'],
        skip: ['id', 'created_at', 'updated_at'], // id موجود في الـ view القديم بس (جدول marketing مفتاحه user_id).
    );

    expect($legacy)->not->toBeEmpty()
        ->and($mismatches)->toBe([]);
});

it('rebuilds premium_active_view from the real production dump', function () {
    $legacy = parityLegacyView('premium_active_view')->keyBy('id');
    $scope = Project::premiumActiveView()->get()->keyBy('id');
    $mismatches = [];

    parityCompare('id', $legacy, $scope, $mismatches, jsonFields: ['details'], skip: ['created_at', 'updated_at']);

    expect($legacy)->not->toBeEmpty()
        ->and($mismatches)->toBe([]);
});
