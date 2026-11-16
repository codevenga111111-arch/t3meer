<?php

namespace App\Console\Commands;

use App\Support\ExhibitionServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * دمج الموردين (material/supplier) في exhibition — القرار 0.5 + مهمة ClickUp #10.
 *
 * الترتيب ملزم كما في SPEC 0.5 «نقل البيانات» (1 → 6):
 *  1) users.user_type: material/supplier → exhibition (الـ slug مايتغيرش).
 *  2) materials → exhibitions (insert لو مفيش صف لنفس user_id) + خدماتها.
 *  3) أعمدة الـ type في الجداول التانية → exhibition (جدول SPEC 0.5).
 *  4) analytics_counts: page=materials تتجمع على showrooms-suppliers.
 *  5) plans و user_permissions: صفوف material تُحذف، وحدود المستخدم المنقول
 *     تبقى كما هي لأن صفه يتحوّل إلى exhibition (SPEC 0.5.5).
 *  6) حذف public_materials_view ثم جدول materials (بعد التأكد من النقل).
 *
 * خصائص:
 * - idempotent: كل خطوة مشروطة بوجود الجدول/العمود وبقاء القيم القديمة،
 *   فإعادة التشغيل بعد النجاح = صفر تغييرات.
 * - متوافق مع الاتنين: قاعدة legacy (أعمدة نصية فيها material) وقاعدة الـ
 *   schema الجديد (أعمدته enum/CHECK ما تقبلش material أصلاً — قرار 7.1#2).
 *   الحماية عبر Schema::hasTable / hasColumn في كل خطوة.
 * - --dry: كل خطوات البيانات تتنفذ داخل transaction ثم تتراجع، فيبقى التقرير
 *   حقيقيًا بدون أي كتابة. خطوة الـ DDL (6) لا تُنفَّذ في dry إطلاقًا.
 * - --database=: اختيار اتصال قاعدة البيانات (لتشغيل الأمر على قاعدة legacy
 *   منفصلة عن الاتصال الحالي).
 *
 * الاستخدام:
 *   php artisan t3meer:merge-materials --dry
 *   php artisan t3meer:merge-materials
 *   php artisan t3meer:merge-materials --database=legacy_dump
 */
class MergeMaterialsIntoExhibitions extends Command
{
    protected $signature = 't3meer:merge-materials
        {--database= : اتصال قاعدة البيانات المستهدف (الافتراضي: الاتصال الحالي)}
        {--dry : تنفيذ كل الخطوات داخل transaction ثم التراجع — تقرير فقط}';

    protected $description = 'دمج الموردين (material/supplier) في exhibition حسب القرار 0.5 — SPEC 0.5';

    /** القيم القديمة الممنوعة نهائيًا (SPEC 7.1 #2). */
    private const LEGACY_TYPES = ['material', 'supplier'];

    /**
     * أعمدة نوع المستخدم في الجداول الأخرى — كلها material → exhibition
     * (الجدول في SPEC 0.5: «قيم type في الجداول التانية»).
     * مستثناها: plans.user_type (تُحذف صفوفها) و rating (عمودين — تحت).
     */
    private const TYPE_COLUMNS = [
        'favorites' => 'type',
        'stats' => 'type',
        'user_validation' => 'account_type',
        'marketing' => 'user_type',
        'subscriptions' => 'user_type',
        'projects' => 'user_type',
        'users' => 'selected_user_type',
    ];

    /** أعمدة rating بالدلالة المزدوجة القديمة أو بعد فصلها (مهمة #5). */
    private const RATING_COLUMNS = ['type', 'target_type', 'writer_type'];

    /** مفاتيح analytics_counts.page اللي بتتجمع في showrooms-suppliers. */
    private const LEGACY_PAGE_KEYS = ['materials', 'material'];

    /** صفحة الدليل الموحدة بعد الدمج (قرار 0.5). */
    private const TARGET_PAGE_KEY = 'showrooms-suppliers';

    /** أعمدة materials/exhibitions المتطابقة التي تُنقل كما هي (SPEC 0.5.2). */
    private const SHARED_COLUMNS = [
        'name', 'governorate', 'city', 'bio', 'whats_app', 'open_hours',
        'main_category', 'experience_years', 'count_of_categories',
        'latitude', 'longitude',
    ];

    private bool $dry = false;

    /** @var array<string, int> عدد الصفوف المتأثرة في كل خطوة */
    private array $report = [];

    /** @var list<string> ملاحظات (أعمدة ناقصة، صفوف ما اتنقلتش…) */
    private array $notes = [];

    /** صفوف materials اللي ما اتنقلتش (user مفقود) — تمنع حذف الجدول. */
    private int $unmigrated = 0;

    /** @var array<string, array<string, true>> أعمدة الجداول (مخزنة مؤقتًا) */
    private array $columns = [];

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry');

        if ($database = $this->option('database')) {
            if (! config("database.connections.{$database}")) {
                $this->error("اتصال غير معرّف في config/database.php: {$database}");

                return self::FAILURE;
            }

            config(['database.default' => $database]);
            $this->info("الاتصال المستهدف: {$database}");
        }

        if (! Schema::hasTable('users')) {
            $this->error('جدول users غير موجود في الاتصال المستهدف.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('exhibitions')) {
            $this->error('جدول exhibitions غير موجود — نفّذ migrations أولًا (تستقبل بيانات الموردين).');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->convertUserTypes();
            $this->copyMaterialsToExhibitions();
            $this->normalizeExistingServices();
            $this->convertTypeColumns();
            $this->mergeAnalyticsPages();
            $this->convertPlansAndPermissions();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('فشل الدمج وتم التراجع عن كل التغييرات: '.$e->getMessage());

            return self::FAILURE;
        }

        // --dry: نفس التقرير بالضبط لكن بتبديل الكتابة (الـ DDL تحت برضه متخطي).
        $this->dry ? DB::rollBack() : DB::commit();

        $this->dropLegacyArtifacts();
        $this->printReport();

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // 1) نوع الحساب في users (الـ slug مايتغيرش — روابط تفضل شغالة).
    // ------------------------------------------------------------------

    private function convertUserTypes(): void
    {
        $this->record('users.user_type → exhibition', DB::table('users')
            ->whereIn('user_type', self::LEGACY_TYPES)
            ->update(['user_type' => 'exhibition']));
    }

    // ------------------------------------------------------------------
    // 2) materials → exhibitions (+ خدمات التخصصات).
    // ------------------------------------------------------------------

    private function copyMaterialsToExhibitions(): void
    {
        if (! Schema::hasTable('materials')) {
            $this->notes[] = 'جدول materials غير موجود — تخطي نقل الملفات.';

            return;
        }

        if (! Schema::hasColumn('materials', 'user_id') || ! Schema::hasColumn('exhibitions', 'user_id')) {
            $this->notes[] = 'عمود user_id مفقود في materials/exhibitions — تخطي نقل الملفات.';

            return;
        }

        $source = $this->columnsOf('materials');
        $target = $this->columnsOf('exhibitions');

        // أعمدة بنفس الاسم على الطرفين (SPEC 0.5.2).
        $shared = array_values(array_intersect(self::SHARED_COLUMNS, array_keys($source), array_keys($target)));
        $copied = 0;
        $existing = 0;

        foreach (DB::table('materials')->get() as $material) {
            $userId = $material->user_id ?? null;

            if ($userId === null || ! DB::table('users')->where('id', $userId)->exists()) {
                // ما نضيعش بيانات: نسجّل الحالة ونمنع حذف الجدول في خطوة 6.
                $this->unmigrated++;
                $this->notes[] = "materials: صف بلا مستخدم موجود (user_id = {$userId}).";

                continue;
            }

            if (DB::table('exhibitions')->where('user_id', $userId)->exists()) {
                $existing++; // SPEC: insert لو مفيش صف لنفس user_id.

                continue;
            }

            $user = DB::table('users')->where('id', $userId)->first();
            $payload = [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            foreach ($shared as $column) {
                $payload[$column] = $material->{$column} ?? null;
            }

            // profile_image → profile_image و logo (SPEC 0.5.2).
            if (isset($source['profile_image'])) {
                if (isset($target['profile_image'])) {
                    $payload['profile_image'] = $material->profile_image ?? null;
                }
                if (isset($target['logo'])) {
                    $payload['logo'] = $material->profile_image ?? null;
                }
            }

            // slug = slug الحساب (مايتغيرش) — ولو محجوز نسبّبه null (عمود فريد).
            $slug = $user->slug ?? null;
            if ($slug !== null && DB::table('exhibitions')->where('slug', $slug)->exists()) {
                $slug = null;
            }
            if (isset($target['slug'])) {
                $payload['slug'] = $slug;
            }

            if (empty($payload['name'])) {
                $payload['name'] = $user->name ?? 'معرض';
            }

            $this->insertWithExistingColumns('exhibitions', $payload);
            $copied++;

            $this->copySpecializations($material, $userId, $slug);
        }

        $this->record('materials → exhibitions', $copied);
        $this->record('materials (ملف موجود مسبقًا — تخطّي)', $existing);
    }

    /**
     * materials.specializations (أكواد materialServices) → صفوف services
     * بالـ label الموحد + أيقونة/لون المعرض (SPEC 0.5.2 و 1.2.6).
     *
     * القيمة المجهولة لا تُهمل: تبقى صف custom بنفس الاسم.
     */
    private function copySpecializations(object $material, string $userId, ?string $slug): void
    {
        if (! isset($this->columnsOf('materials')['specializations']) || ! Schema::hasTable('services')) {
            return;
        }

        foreach ($this->parseList($material->specializations ?? null) as $raw) {
            $code = ExhibitionServices::resolve($raw);
            $name = $code !== null
                ? ExhibitionServices::ALL[$code]
                : (is_scalar($raw) ? trim((string) $raw) : '');

            if ($name === '') {
                continue;
            }

            // idempotent: نفس الخدمة ما تتوظرش لنفس المستخدم.
            if (DB::table('services')->where('user_id', $userId)->where('name', $name)->exists()) {
                continue;
            }

            $this->insertWithExistingColumns('services', [
                'user_id' => $userId,
                'name' => $name,
                'icon' => ExhibitionServices::ICON,
                'color' => ExhibitionServices::COLOR,
                'slug' => $slug ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->record('specializations → services', 1);
        }
    }

    /**
     * توحيد services اللي اتكتبت بالكود/الlabel القديم لمسميّات المورد
     * (لو الواجهة القديمة كتبتها في services بدل materials.specializations).
     */
    private function normalizeExistingServices(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'name')) {
            return;
        }

        $affected = 0;

        foreach (ExhibitionServices::LEGACY_MAP as $legacyCode => $code) {
            $legacyNames = array_filter([
                $legacyCode,
                ExhibitionServices::LEGACY_MATERIAL[$legacyCode] ?? null,
            ]);

            foreach ($legacyNames as $oldName) {
                $affected += DB::table('services')
                    ->where('name', $oldName)
                    ->update(['name' => ExhibitionServices::ALL[$code]]);
            }
        }

        $this->record('services: توحيد الأسماء القديمة', $affected);
    }

    // ------------------------------------------------------------------
    // 3) أعمدة الـ type في الجداول التانية.
    // ------------------------------------------------------------------

    private function convertTypeColumns(): void
    {
        foreach (self::TYPE_COLUMNS as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $this->record("{$table}.{$column} → exhibition", DB::table($table)
                ->whereIn($column, self::LEGACY_TYPES)
                ->update([$column => 'exhibition']));
        }

        if (! Schema::hasTable('rating')) {
            return;
        }

        foreach (self::RATING_COLUMNS as $column) {
            if (! Schema::hasColumn('rating', $column)) {
                continue;
            }

            $this->record("rating.{$column} → exhibition", DB::table('rating')
                ->whereIn($column, self::LEGACY_TYPES)
                ->update([$column => 'exhibition']));
        }
    }

    // ------------------------------------------------------------------
    // 4) analytics_counts: page=materials → showrooms-suppliers.
    // ------------------------------------------------------------------

    private function mergeAnalyticsPages(): void
    {
        if (! Schema::hasTable('analytics_counts')) {
            return;
        }

        $sources = DB::table('analytics_counts')
            ->where('stat_type', 'page')
            ->whereIn('stat_key', self::LEGACY_PAGE_KEYS)
            ->get();

        if ($sources->isEmpty()) {
            return;
        }

        $total = (int) $sources->sum('total_count');
        $target = DB::table('analytics_counts')
            ->where('stat_type', 'page')
            ->where('stat_key', self::TARGET_PAGE_KEY)
            ->first();

        if ($target === null) {
            $this->insertWithExistingColumns('analytics_counts', [
                'stat_type' => 'page',
                'stat_key' => self::TARGET_PAGE_KEY,
                'total_count' => $total,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('analytics_counts')->where('id', $target->id)
                ->increment('total_count', $total);
        }

        DB::table('analytics_counts')->whereIn('id', $sources->pluck('id'))->delete();

        $this->record('analytics_counts: دمج page=materials في showrooms-suppliers', $sources->count());
    }

    // ------------------------------------------------------------------
    // 5) plans و user_permissions.
    // ------------------------------------------------------------------

    private function convertPlansAndPermissions(): void
    {
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'user_type')) {
            $this->record('plans: حذف صفوف material/supplier', DB::table('plans')
                ->whereIn('user_type', self::LEGACY_TYPES)
                ->delete());
        }

        if (! Schema::hasTable('user_permissions') || ! Schema::hasColumn('user_permissions', 'user_type')) {
            return;
        }

        // صفوف المستخدمين المنقولين: نتحولها exhibition فتبقى حدود باقتهم كما هي
        // (SPEC 0.5.5: «بياخدوا حدود باقة exhibition المماثلة»).
        $this->record('user_permissions: صفوف المستخدمين → exhibition', DB::table('user_permissions')
            ->whereNotNull('user_id')
            ->whereIn('user_type', self::LEGACY_TYPES)
            ->update(['user_type' => 'exhibition']));

        // صفوف الـ template (user_id null): تُحذف — نظائرها لـ exhibition
        // بيتزرعوا من LookupsSeeder/استيراد plans (قرار #6).
        $this->record('user_permissions: حذف صفوف material/supplier template', DB::table('user_permissions')
            ->whereNull('user_id')
            ->whereIn('user_type', self::LEGACY_TYPES)
            ->delete());
    }

    // ------------------------------------------------------------------
    // 6) الحذف النهائي — بعد التأكد من اكتمال النقل، ولا يعمل في --dry.
    // ------------------------------------------------------------------

    private function dropLegacyArtifacts(): void
    {
        if ($this->dry) {
            $this->notes[] = '--dry: تم تخطي حذف public_materials_view و materials.';

            return;
        }

        if ($this->unmigrated > 0) {
            $this->notes[] = "⚠ {$this->unmigrated} صف في materials ما اتنقلش — الجداول القديمة اتسابت لمراجعة يدوية.";

            return;
        }

        // الـ view قبل الجدول (بيعتمد عليه) — ما نسجّلش الحذف لو مش موجود أصلًا.
        if ($this->legacyViewExists()) {
            try {
                DB::statement('DROP VIEW IF EXISTS public_materials_view');
                $this->record('حذف public_materials_view', 1);
            } catch (Throwable $e) {
                $this->notes[] = 'تعذّر حذف public_materials_view: '.$e->getMessage();
            }
        }

        if (Schema::hasTable('materials')) {
            Schema::dropIfExists('materials');
            $this->record('حذف جدول materials', 1);
        }
    }

    // ------------------------------------------------------------------
    // مساعدات
    // ------------------------------------------------------------------

    /**
     * هل public_materials_view موجودة فعلًا؟ (Schema::hasTable ما بتشوفش الـ views
     * في SQLite — فنستعلم من الـ catalog حسب الـ driver قبل الحذف).
     */
    private function legacyViewExists(): bool
    {
        $view = 'public_materials_view';

        $rows = match (DB::connection()->getDriverName()) {
            'sqlite' => DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'view' AND name = ?", [$view]
            ),
            'pgsql' => DB::select(
                'SELECT table_name FROM information_schema.views WHERE table_schema = current_schema() AND table_name = ?', [$view]
            ),
            default => DB::select(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? AND table_type = 'VIEW'", [$view]
            ),
        };

        return $rows !== [];
    }

    /** إدراج بأعمدة الجدول الموجودة فقط (قراسته تحمي من فروق legacy/new schema). */
    private function insertWithExistingColumns(string $table, array $payload): void
    {
        $payload = array_intersect_key($payload, $this->columnsOf($table));

        if ($payload !== []) {
            DB::table($table)->insert($payload);
        }
    }

    /**
     * أعمدة جدول محددة كمفتاح (معبّأ true) — مخزنة لتفادي استعلامات متكررة.
     *
     * @return array<string, true>
     */
    private function columnsOf(string $table): array
    {
        return $this->columns[$table] ??= array_fill_keys(
            array_column(Schema::getColumns($table), 'name'),
            true
        );
    }

    /**
     * قراءة قيمة list (JSON array / مصفوفة Postgres `{a,b}` / مفصولة بفواصل).
     *
     * @return array<int, mixed>
     */
    private function parseList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $raw = substr($raw, 1, -1);
        }

        if ($raw === '') {
            return [];
        }

        // القيم المخزنة أكواد بسيطة (site-delivery) بلا فواصل داخلها — split آمن.
        $parts = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(
            static fn (string $part): string => trim($part, " \t\n\r\0\x0B'\""),
            $parts
        );
    }

    private function record(string $step, int $affected): void
    {
        $this->report[$step] = ($this->report[$step] ?? 0) + $affected;
    }

    private function printReport(): void
    {
        $rows = collect($this->report)->filter(fn (int $count): bool => $count > 0);

        if ($rows->isEmpty()) {
            $this->info('مفيش أي صف قديم — القاعدة خالية من material/supplier.');
        } else {
            $this->table(
                ['الخطوة', 'المتأثر'],
                $rows->map(fn (int $count, string $step): array => [$step, $count])->values()->all()
            );
        }

        foreach ($this->notes as $note) {
            $this->warn('• '.$note);
        }

        if ($this->dry) {
            $this->comment('--dry: تم تنفيذ كل الخطوات داخل transaction ثم التراجع — مفيش أي كتابة.');
        } else {
            $this->info('تم حرق الدمج بنجاح. 🎉');
        }
    }
}
