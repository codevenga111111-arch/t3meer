<?php

namespace App\Console\Commands;

use App\Support\Slug;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ترحيل بيانات البروداكشن من ملفات Supabase/*.sql إلى MySQL (Laravel).
 *
 * المصدر: Supabase/*_rows.sql (تصدير INSERT FROM public.<table>) — المراجع:
 * - T3MEER_BACKEND_API_SPEC_v3.md: 1.2 (الجداول), 0.5 (المورد=exhibition), 7.1 (القرارات)
 * - T3MER_CLICKUP_PLAN.md — تاسكة "بناء schema + حسم القرارات + ترحيل الداتا الحية"
 *
 * القواعد المطبقة أثناء الترحيل:
 * - توحيد القيم: material/supplier→exhibition · pro→professional · reject→rejected · t/f→1/0
 * - text[] → JSON · jsonb → JSON (متوافق) · timestamptz → TIMESTAMP بدون كسور
 * - توحيد التليفونات: أرقام إنجليزية (toEnglishDigits)
 * - الملفات الثلاثة views (marketing_active_users, premium_active_view, public_offices_view)
 *   بتتخطى لأنها مش جداول — بتتبني كاستعلامات في Laravel.
 * - جدول plans بينشئ صفوف template في user_permissions (الحدود لكل باقة — قرار #6).
 * - stats بيتحطّط بنفس (slug, metric, date) بجمع count (قرار #8 grain يومي).
 * - مفيش trigger/stored procedure — كل التحويلات في PHP.
 * - idempotent: أي صف مكرر بيتجاهل (INSERT IGNORE) — التشغيل التاني آمن.
 *
 * الاستخدام:
 *   php artisan t3meer:import-supabase            # استيراد
 *   php artisan t3meer:import-supabase --fresh     # مسح الجداول المستهدفة أولاً
 *   php artisan t3meer:import-supabase --dry       # بدون كتابة (تقرير بس)
 */
class ImportSupabaseData extends Command
{
    protected $signature = 't3meer:import-supabase
        {--path=Supabase : مجلد ملفات SQL}
        {--fresh : مسح الجداول المستهدفة قبل الاستيراد}
        {--dry : عرض التقرير بدون أي كتابة}';

    protected $description = 'ترحيل بيانات البروداكشن من Supabase/*.sql إلى قاعدة MySQL الجديدة';

    /** جدول المصدر ← ملف التصدير (بالترتيب — الأولوية للـ FK). */
    private const ORDER = [
        'users', 'user_roles', 'engineers', 'offices', 'exhibitions',
        'user_validation', 'plans', 'subscriptions', 'subscription_payments',
        'user_permissions', 'services', 'projects', 'products',
        'customers', 'favorites', 'rating', 'stats',
        'analytics_counts', 'marketing',
    ];

    /** ملفات views بتتخطى (مش جداول). */
    private const SKIP_FILES = ['marketing_active_users', 'premium_active_view', 'public_offices_view'];

    /** @var array<string, array{source:int, imported:int, skipped:int, dup:int}> */
    private array $report = [];

    /** @var array<string, array<string, int>> سبب التخطي حسب الجدول */
    private array $skippedReasons = [];

    /** @var array<string, int> تحذيرات (قيم اتحوّلت لافتراضي) */
    private array $warnings = [];

    /** @var array<string, true> معرّفات المستخدمين للتحقق من الـ FK */
    private array $userIds = [];

    /** @var array<string, string> user_id → user_type (لتفكيك rating.type المزدوجة). */
    private array $userTypes = [];

    /** @var array<string, true> تتبع المفاتيح المكررة ( UNIQUE ) */
    private array $seen = [];

    /** @var array<int, array<string, mixed>> صفوف template للحدود (من plans) */
    private array $planTemplates = [];

    /** @var array<string, array<string, mixed>> تجميع stats حسب (slug|metric|date) */
    private array $statsAgg = [];

    /** @var array<string, true> معرّفات الاشتراكات للتحقق من الـ FK */
    private array $subscriptionIds = [];

    private bool $dry = false;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry');
        $dir = base_path($this->option('path'));

        if (! is_dir($dir)) {
            $this->error("المجلد غير موجود: {$dir}");

            return self::FAILURE;
        }

        if ($this->option('fresh') && ! $this->dry) {
            $this->freshTables();
        }

        $this->userIds = DB::table('users')->pluck('id')->flip()->all();
        // نوع كل مستخدم — يُستخدم في rating.writer_type (فصل الدلالة المزدوجة).
        $this->userTypes = DB::table('users')->pluck('user_type', 'id')->all();

        foreach (self::ORDER as $table) {
            $file = $dir.DIRECTORY_SEPARATOR.$table.'_rows.sql';

            if (! is_file($file)) {
                $this->warn("⚠ ملف ناقص: {$table}_rows.sql");

                continue;
            }

            $this->importFile($table, $file);
        }

        // صفوف template للحدود (من جدول plans) — قرار #6.
        $this->importPlanPermissionTemplates($dir);

        $this->printReport();

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // القراءة والتحليل
    // ------------------------------------------------------------------

    private function importFile(string $table, string $file): void
    {
        $sql = file_get_contents($file);

        if ($sql === false) {
            $this->error("تعذّرت قراءة: {$file}");

            return;
        }

        $this->report[$table] ??= ['source' => 0, 'imported' => 0, 'skipped' => 0, 'dup' => 0];

        foreach ($this->parseInserts($sql) as [$cols, $tuples]) {
            foreach ($tuples as $tuple) {
                $row = [];
                foreach ($cols as $i => $col) {
                    $row[$col] = $tuple[$i] ?? null;
                }

                $this->report[$table]['source']++;

                $mapped = $this->mapRow($table, $row);

                if ($mapped === null) {
                    continue; // السبب اتسجّل في skip()
                }

                if ($this->dry) {
                    $this->report[$table]['imported']++;

                    continue;
                }

                $this->insertRow($table, $mapped);
            }
        }

        // تحميل معرّفات الاشتراكات بعد استيرادها (للتحقق في المدفوعات).
        if ($table === 'subscriptions' && ! $this->dry) {
            $this->subscriptionIds = DB::table('subscriptions')->pluck('id')->flip()->all();
        }

        if ($table === 'users' && ! $this->dry) {
            $this->userIds = DB::table('users')->pluck('id')->flip()->all();
            $this->userTypes = DB::table('users')->pluck('user_type', 'id')->all();
        }
    }

    /**
     * تحليل كل عبارات INSERT INTO public.x (cols) VALUES (...), (...);
     * محلل خاص بآلة حالتين يتحمّل اقتباسات مفردة مكررة '' وقوائم {..}.
     *
     * @return array<int, array{0: string[], 1: array<int, array<int, mixed>>}>
     */
    private function parseInserts(string $sql): array
    {
        $statements = [];

        if (! preg_match_all(
            '/INSERT\s+INTO\s+public\.(\w+)\s*\(([^)]*)\)\s*VALUES/i',
            $sql,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            return [];
        }

        foreach ($matches as $m) {
            $table = $m[1][0];
            $columns = array_map(
                fn ($c) => trim($c, " \t\n\r\0\x0B\"'"),
                explode(',', $m[2][0])
            );
            $pos = $m[0][1] + strlen($m[0][0]);
            $tuples = $this->parseTuples($sql, $pos);
            $statements[] = [$table, $columns, $tuples];
        }

        // نرجّع الأعمدة مع كل مجموعة (الملف الواحد جدول واحد عملياً).
        $out = [];
        foreach ($statements as [, $cols, $tuples]) {
            $out[] = [$cols, $tuples];
        }

        return $out;
    }

    /** @return array<int, array<int, mixed>> */
    private function parseTuples(string $s, int &$pos): array
    {
        $rows = [];
        $len = strlen($s);

        while ($pos < $len) {
            $c = $s[$pos];

            if ($c === ' ' || $c === "\n" || $c === "\r" || $c === "\t" || $c === ',') {
                $pos++;

                continue;
            }

            if ($c === ';') {
                break;
            }

            if ($c !== '(') {
                break; // نهاية الملف أو محتوى غير متوقع
            }

            $pos++; // (
            $depth = 1;
            $inStr = false;
            $fieldStart = $pos;
            $fields = [];

            while ($pos < $len) {
                $ch = $s[$pos];

                if ($inStr) {
                    if ($ch === "'") {
                        if ($pos + 1 < $len && $s[$pos + 1] === "'") {
                            $pos += 2; // اقتباس مهرب ''

                            continue;
                        }
                        $inStr = false;
                    }
                    $pos++;

                    continue;
                }

                if ($ch === "'") {
                    $inStr = true;
                    $pos++;

                    continue;
                }

                if ($ch === '(') {
                    $depth++;
                    $pos++;

                    continue;
                }

                if ($ch === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $fields[] = substr($s, $fieldStart, $pos - $fieldStart);
                        $pos++;

                        break;
                    }
                    $pos++;

                    continue;
                }

                if ($ch === ',' && $depth === 1) {
                    $fields[] = substr($s, $fieldStart, $pos - $fieldStart);
                    $pos++;
                    $fieldStart = $pos;

                    continue;
                }

                $pos++;
            }

            $rows[] = array_map($this->decodeValue(...), array_map('trim', $fields));
        }

        return $rows;
    }

    /** تحويل قيمة SQL الخام إلى PHP: NULL / نص بدون اقتباس / عدد / منطقي. */
    private function decodeValue(string $raw): mixed
    {
        if ($raw === '' || strcasecmp($raw, 'NULL') === 0) {
            return null;
        }

        if (strlen($raw) >= 2 && $raw[0] === "'" && substr($raw, -1) === "'") {
            return str_replace("''", "'", substr($raw, 1, -1));
        }

        if ($raw === 't' || $raw === 'true') {
            return 1;
        }

        if ($raw === 'f' || $raw === 'false') {
            return 0;
        }

        if (is_numeric($raw)) {
            return $raw + 0;
        }

        return $raw;
    }

    // ------------------------------------------------------------------
    // التحويل لكل جدول (القرارات 7.1 مطبقة هنا)
    // ------------------------------------------------------------------

    private function mapRow(string $table, array $row): ?array
    {
        return match ($table) {
            'users' => $this->mapUsers($row),
            'user_roles' => $this->mapUserRoles($row),
            'engineers' => $this->mapEngineers($row),
            'offices' => $this->mapOffices($row),
            'exhibitions' => $this->mapExhibitions($row),
            'user_validation' => $this->mapValidation($row),
            'plans' => $this->mapPlans($row),
            'subscriptions' => $this->mapSubscriptions($row),
            'subscription_payments' => $this->mapPayments($row),
            'user_permissions' => $this->mapPermissions($row),
            'services' => $this->mapServices($row),
            'projects' => $this->mapProjects($row),
            'products' => $this->mapProducts($row),
            'customers' => $this->mapCustomers($row),
            'favorites' => $this->mapFavorites($row),
            'rating' => $this->mapRating($row),
            'stats' => $this->mapStats($row),
            'analytics_counts' => $this->mapAnalytics($row),
            'marketing' => $this->mapMarketing($row),
            default => null,
        };
    }

    private function mapUsers(array $r): ?array
    {
        $id = (string) ($r['id'] ?? '');

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return $this->skip('users', 'id_invalid');
        }

        $email = strtolower(trim((string) ($r['email'] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (! str_contains($email, '@')) {
                return $this->skip('users', 'email_invalid');
            }

            // بريد ناقص TLD (بيانات بروداكشن) — نُبقي الحساب ونُنبّه بدل فقده.
            $this->warnOnce('email_broken', "بريد غير صالح (ناقص TLD) أُبقي كما هو: {$email}");
        }

        if (isset($this->userIds[$id]) || isset($this->seen['users.email'][$email])) {
            return $this->skip('users', 'duplicate', false);
        }

        $slug = strtolower(trim((string) ($r['slug'] ?? '')));

        if ($slug === '') {
            $slug = Slug::fromEmail($email);
        }

        if (isset($this->seen['users.slug'][$slug])) {
            $slug .= '-'.count($this->seen['users.slug']);
        }

        $phone = normalizePhone((string) ($r['phone'] ?? ''));

        $this->seen['users.email'][$email] = true;
        $this->seen['users.slug'][$slug] = true;
        $this->userIds[$id] = true; // للتحقق في الوضع التجريبي وقبل إعادة تحميل القاعدة
        if ($phone !== '') {
            if (isset($this->seen['users.phone'][$phone])) {
                $phone = null; // رقم مكرر — نُبطله بدل ما يكسر UNIQUE (تقرير)
                $this->warnOnce('phone_duplicate', 'يوجد تليفونات مكررة في users → اتكتبت NULL');
            } else {
                $this->seen['users.phone'][$phone] = true;
            }
        }

        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => $id,
            'email' => $email,
            'password' => null, // حسابات إنتاجية بدون كلمة مرور — عبر "نسيت كلمة المرور"
            // قرار: البريد مؤكد لأنها حسابات شغالة على البروداكشن.
            'email_verified_at' => $created,
            'name' => $this->str($r['name'] ?? null, 'مستخدم'),
            'phone' => $phone,
            'user_type' => $this->mapUserType($r['user_type'] ?? null, 'engineer'),
            'slug' => $slug,
            'current_step' => max(0, min(4, (int) ($r['current_step'] ?? 1))),
            'status' => $this->mapUserStatus($r['status'] ?? null),
            'selected_plan' => $this->mapPlanType($r['selected_plan'] ?? null, 'free'),
            'selected_billing_cycle' => null,
            'selected_user_type' => null,
            'rejection_reason' => $this->strOrNull($r['rejection_reason'] ?? null),
            'created_at' => $created,
            'updated_at' => $this->ts($r['updated_at'] ?? null) ?? $created,
        ];
    }

    private function mapUserRoles(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('user_roles', 'fk_user');
        }

        $role = strtolower(trim((string) ($r['role'] ?? '')));

        if ($role === '') {
            return $this->skip('user_roles', 'role_empty');
        }

        $key = $userId.'|'.$role;

        if (isset($this->seen['user_roles'][$key])) {
            return $this->skip('user_roles', 'duplicate', false);
        }

        $this->seen['user_roles'][$key] = true;
        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'role' => $role,
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapEngineers(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('engineers', 'fk_user');
        }

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'slug' => strtolower($this->str($r['slug'] ?? null, '')),
            'phone' => $this->phoneOrNull($r['phone'] ?? null),
            'major' => $this->strOrNull($r['major'] ?? null),
            'experience_years' => (int) ($r['experience_years'] ?? 0),
            'governorate' => $this->strOrNull($r['governorate'] ?? null),
            'city' => null,
            'bio' => $this->strOrNull($r['bio'] ?? null),
            'whats_app' => $this->phoneOrNull($r['whats_app'] ?? null),
            'website' => null,
            'working_hours' => null,
            'social' => null,
            'skills' => $this->pgArray($r['skills'] ?? null),
            'latitude' => $this->numOrNull($r['latitude'] ?? null),
            'longitude' => $this->numOrNull($r['longitude'] ?? null),
            'profile_image' => $this->strOrNull($r['profile_image'] ?? null),
            'cover_image' => null,
            'video_url' => null,
            'video' => $this->strOrNull($r['video'] ?? null),
            'facebook' => $this->strOrNull($r['facebook'] ?? null),
            'instagram' => $this->strOrNull($r['instagram'] ?? null),
            'youtube' => $this->strOrNull($r['youtube'] ?? null),
            'created_at' => $this->ts($r['created_at'] ?? null),
            'updated_at' => $this->ts($r['updated_at'] ?? null),
        ];
    }

    private function mapOffices(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('offices', 'fk_user');
        }

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'slug' => strtolower($this->str($r['slug'] ?? null, '')),
            'governorate' => $this->strOrNull($r['governorate'] ?? null),
            'bio' => $this->strOrNull($r['bio'] ?? null),
            'whats_app' => $this->phoneOrNull($r['whats_app'] ?? null),
            'experience_years' => (int) ($r['experience_years'] ?? 0),
            'count_of_projects' => (int) ($r['count_of_projects'] ?? 0),
            'open_hours' => $this->strOrNull($r['open_hours'] ?? null),
            'latitude' => $this->numOrNull($r['latitude'] ?? null),
            'longitude' => $this->numOrNull($r['longitude'] ?? null),
            'profile_image' => $this->strOrNull($r['profile_image'] ?? null),
            'video' => $this->strOrNull($r['video'] ?? null),
            'facebook' => $this->str($r['facebook'] ?? null, ''),
            'instagram' => $this->str($r['instagram'] ?? null, ''),
            'youtube' => $this->str($r['youtube'] ?? null, ''),
            'created_at' => $this->ts($r['created_at'] ?? null),
            'updated_at' => $this->ts($r['updated_at'] ?? null),
        ];
    }

    private function mapExhibitions(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('exhibitions', 'fk_user');
        }

        $slug = $this->strOrNull($r['slug'] ?? null);
        $slug = $slug !== null ? strtolower($slug) : null;

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'slug' => $slug,
            'main_category' => $this->strOrNull($r['main_category'] ?? null),
            'governorate' => $this->strOrNull($r['governorate'] ?? null),
            'city' => $this->strOrNull($r['city'] ?? null),
            'bio' => $this->strOrNull($r['bio'] ?? null),
            'whats_app' => $this->phoneOrNull($r['whats_app'] ?? null),
            'experience_years' => (int) ($r['experience_years'] ?? 0),
            'latitude' => $this->numOrNull($r['latitude'] ?? null),
            'longitude' => $this->numOrNull($r['longitude'] ?? null),
            'logo' => $this->strOrNull($r['logo'] ?? null),
            'banner' => $this->strOrNull($r['banner'] ?? null),
            'profile_image' => $this->strOrNull($r['profile_image'] ?? null),
            'count_of_categories' => $r['count_of_categories'] !== null ? (int) $r['count_of_categories'] : null,
            'open_hours' => $this->strOrNull($r['open_hours'] ?? null),
            'video' => $this->strOrNull($r['video'] ?? null),
            'brands' => $this->pgArray($r['brands'] ?? null),
            'facebook' => $this->strOrNull($r['facebook'] ?? null),
            'instagram' => $this->strOrNull($r['instagram'] ?? null),
            'youtube' => $this->strOrNull($r['youtube'] ?? null),
            'created_at' => $this->ts($r['created_at'] ?? null),
            'updated_at' => $this->ts($r['updated_at'] ?? null),
        ];
    }

    private function mapValidation(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('user_validation', 'fk_user');
        }

        $status = strtolower((string) ($r['status'] ?? 'pending'));

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'account_type' => in_array($r['account_type'] ?? '', ['engineer', 'office', 'exhibition'], true)
                ? $r['account_type']
                : ($r['account_type'] !== null ? $this->mapUserType($r['account_type']) : null),
            'professional_id' => $this->strOrNull($r['professional_id'] ?? null),
            'tax_number' => $this->strOrNull($r['tax_number'] ?? null),
            'documents' => $this->jsonOrNull($r['documents'] ?? null),
            'details' => $this->jsonOrNull($r['details'] ?? null),
            // قرار #4: reject → rejected (الأدمن القديم كان يكتب reject)
            'status' => in_array($status, ['pending', 'active', 'rejected'], true)
                ? $status
                : ($status === 'reject' ? 'rejected' : 'pending'),
            'rejection_reason' => $this->strOrNull($r['rejection_reason'] ?? null),
            'created_at' => $this->ts($r['created_at'] ?? null),
            'updated_at' => $this->ts($r['updated_at'] ?? null),
        ];
    }

    private function mapPlans(array $r): ?array
    {
        $userType = $this->mapUserType($r['user_type'] ?? null, null);

        if ($userType === null || $userType === 'customer' || $userType === 'admin') {
            return $this->skip('plans', 'user_type_invalid');
        }

        $planType = $this->mapPlanType($r['plan_type'] ?? null, null);

        if ($planType === null || $planType === 'free') {
            // free مش في جدول الأسعار (مجاني افتراضي) — نسجّله عشان الحدود.
            if ($planType !== 'free') {
                return $this->skip('plans', 'plan_type_invalid');
            }
        }

        $cycle = $this->mapBillingCycle($r['billing_cycle'] ?? null);

        if ($cycle === null) {
            return $this->skip('plans', 'billing_cycle_invalid');
        }

        $key = $userType.'|'.$planType.'|'.$cycle;

        if (isset($this->seen['plans'][$key])) {
            return $this->skip('plans', 'duplicate', false);
        }

        $this->seen['plans'][$key] = true;
        $now = now()->toDateTimeString();

        // صفوف template للحدود (قرار #6) — تتسجّل بعد الاستيراد.
        $this->planTemplates[] = [
            'user_id' => null,
            'user_type' => $userType,
            'plan_type' => $planType,
            'max_visits' => $this->intOrNull($r['max_monthly_visits'] ?? null),
            'max_projects' => $this->intOrNull($r['max_resources'] ?? null),
            'max_services' => $this->intOrNull($r['max_services'] ?? null),
            'max_requests' => $this->intOrNull($r['max_requests'] ?? null),
            'whatsapp_tracking' => (int) ($r['whatsapp_tracking'] ?? 0),
            'call_tracking' => (int) ($r['call_tracking'] ?? 0),
            'detailed_analytics' => (int) ($r['detailed_analytics'] ?? 0),
            'video_projects' => (int) ($r['video_projects'] ?? 0),
            'google_maps_location' => (int) ($r['google_maps_location'] ?? 0),
            'directory_ad_banner' => (int) ($r['directory_ad_banner'] ?? 0),
            'distinguished_badge' => (int) ($r['distinguished_badge'] ?? 0),
            'homepage_ad_banner' => (int) ($r['homepage_ad_banner'] ?? 0),
            'social_media_promo' => (int) ($r['social_media_promo'] ?? 0),
            'facebook_pixel' => (int) ($r['facebook_pixel'] ?? 0),
            'priority_search' => $this->mapPriority($r['priority_search'] ?? null),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [
            'user_type' => $userType,
            'plan_type' => $planType,
            'billing_cycle' => $cycle,
            'price' => (float) ($r['price'] ?? 0),
            'original_price' => $r['original_price'] !== null ? (float) $r['original_price'] : null,
            'plan_slug' => $this->strOrNull($r['plan_slug'] ?? null),
            'duration_days' => $this->intOrNull($r['duration_days'] ?? null),
            'weight' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function mapSubscriptions(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('subscriptions', 'fk_user');
        }

        if (isset($this->seen['subscriptions'][$userId])) {
            return $this->skip('subscriptions', 'duplicate', false);
        }

        $this->seen['subscriptions'][$userId] = true;

        $status = strtolower((string) ($r['status'] ?? 'pending'));
        $status = match ($status) {
            'pending', 'active', 'expired', 'rejected' => $status,
            'approved' => 'active', // TS قديم يستخدم approved
            'reject' => 'rejected',
            default => 'pending',
        };

        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'plan_type' => $this->mapPlanType($r['plan_type'] ?? null, 'free'),
            'plan_slug' => $this->strOrNull($r['plan_slug'] ?? null),
            'billing_cycle' => $this->mapBillingCycle($r['billing_cycle'] ?? null),
            'user_type' => $this->mapUserType($r['user_type'] ?? null, null),
            // قرار #5: price من plan_price (كانت plan_price/amount مكررة)
            'price' => $r['plan_price'] !== null ? (float) $r['plan_price'] : null,
            'confirmation' => $this->strOrNull($r['confirmation'] ?? null),
            'status' => $status,
            // قرار #5: starts_at من start_date (كانت start_date/starts_at مكررة)
            'starts_at' => $this->ts($r['start_date'] ?? null),
            'expires_at' => $this->ts($r['expires_at'] ?? null),
            'is_renewal' => 0,
            'created_at' => $created,
            'updated_at' => $this->ts($r['updated_at'] ?? null) ?? $created,
        ];
    }

    private function mapPayments(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');
        $subId = (string) ($r['subscription_id'] ?? '');

        if (! $this->userExists($userId)) {
            $userId = null; // حساب محذوف — نُبقي الدفعة المالية بدون ربط (تقرير)
            $this->warnOnce('payment_orphan_user', 'يوجد مدفوعات لحسابات محذوفة → user_id = NULL (السجل المالي محفوظ)');
            $subId = null;
        }

        if ($subId !== '' && ! isset($this->subscriptionIds[$subId])) {
            $subId = null; // اشتراك محذوف — نُبقي الدفعة بدون ربط (تقرير)
            $this->warnOnce('payment_orphan', 'يوجد مدفوعات بدون اشتراك مطابق → subscription_id = NULL');
        }

        $created = $this->ts($r['created_at'] ?? null) ?? $this->ts($r['paid_at'] ?? null);

        return [
            'id' => (string) $r['id'],
            'subscription_id' => $subId,
            'user_id' => $userId,
            'amount' => (float) ($r['amount'] ?? 0),
            'plan_type' => $this->mapPlanType($r['plan_type'] ?? null, 'free'),
            'billing_cycle' => $this->mapBillingCycle($r['billing_cycle'] ?? null),
            'status' => 'active',
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapPermissions(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('user_permissions', 'fk_user');
        }

        if (isset($this->seen['user_permissions'][$userId])) {
            return $this->skip('user_permissions', 'duplicate', false);
        }

        $this->seen['user_permissions'][$userId] = true;
        $now = now()->toDateTimeString();

        return [
            'user_id' => $userId,
            'user_type' => $this->mapUserType($r['user_type'] ?? null, null),
            'plan_type' => $this->mapPlanType($r['plan_type'] ?? null, 'free'),
            'max_visits' => $this->intOrNull($r['max_visits'] ?? null),
            'max_projects' => $this->intOrNull($r['max_projects'] ?? null),
            'max_services' => $this->intOrNull($r['max_services'] ?? null),
            'max_requests' => $this->intOrNull($r['max_requests'] ?? null),
            'whatsapp_tracking' => (int) ($r['whatsapp_tracking'] ?? 0),
            'call_tracking' => (int) ($r['call_tracking'] ?? 0),
            'detailed_analytics' => (int) ($r['detailed_analytics'] ?? 0),
            'video_projects' => (int) ($r['video_projects'] ?? 0),
            'google_maps_location' => (int) ($r['google_maps_location'] ?? 0),
            'directory_ad_banner' => (int) ($r['directory_ad_banner'] ?? 0),
            'distinguished_badge' => (int) ($r['distinguished_badge'] ?? 0),
            'homepage_ad_banner' => (int) ($r['homepage_ad_banner'] ?? 0),
            'social_media_promo' => (int) ($r['social_media_promo'] ?? 0),
            'facebook_pixel' => (int) ($r['facebook_pixel'] ?? 0),
            'priority_search' => $this->mapPriority($r['priority_search'] ?? null),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function mapServices(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('services', 'fk_user');
        }

        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (int) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'icon' => $this->strOrNull($r['icon'] ?? null),
            'color' => $this->str($r['color'] ?? null, '#0070d7'),
            'slug' => strtolower($this->str($r['slug'] ?? null, '')),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapProjects(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('projects', 'fk_user');
        }

        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'location' => $this->strOrNull($r['location'] ?? null),
            'area' => $this->numOrNull($r['area'] ?? null),
            'type' => $this->strOrNull($r['type'] ?? null),
            'image' => $this->strOrNull($r['image'] ?? null),
            'slug' => strtolower($this->str($r['slug'] ?? null, '')),
            'user_type' => $this->mapUserType($r['user_type'] ?? null, null),
            'user_name' => $this->strOrNull($r['user_name'] ?? null),
            'views' => 0,
            'details' => $this->jsonOrNull($r['details'] ?? null),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapProducts(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('products', 'fk_user');
        }

        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (int) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'category' => $this->strOrNull($r['category'] ?? null),
            'sub_category' => $this->strOrNull($r['sub_category'] ?? null),
            'image' => $this->strOrNull($r['image'] ?? null),
            'price' => $this->numOrNull($r['price'] ?? null),
            'slug' => strtolower($this->str($r['slug'] ?? null, '')),
            'details' => $this->jsonOrNull($r['details'] ?? null),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapCustomers(array $r): ?array
    {
        $userId = $r['user_id'] !== null ? (string) $r['user_id'] : null;

        if ($userId !== null && ! $this->userExists($userId)) {
            $userId = null;
            $this->warnOnce('customer_user_missing', 'يوجد طلبات بمستخدم محذوف → user_id = NULL');
        }

        $type = (string) ($r['customer_type'] ?? '');

        if (! in_array($type, ['guest', 'need_service'], true)) {
            return $this->skip('customers', 'customer_type_invalid');
        }

        $status = strtolower((string) ($r['status'] ?? 'pending'));
        $allowed = ['pending', 'active', 'assigned', 'approved', 'in_progress', 'completed', 'cancelled', 'refused', 'deleted'];

        if (! in_array($status, $allowed, true)) {
            $this->warnOnce('customer_status', "status غير معروف '{$status}' → pending");
            $status = 'pending';
        }

        $provider = $r['assigned_provider_id'] !== null ? (string) $r['assigned_provider_id'] : null;

        if ($provider !== null && ! $this->userExists($provider)) {
            $provider = null;
        }

        $created = $this->ts($r['created_at'] ?? null);

        $response = in_array($r['customer_response'] ?? '', ['approved', 'refused'], true)
            ? $r['customer_response']
            : null;

        return [
            'id' => (string) $r['id'],
            'user_id' => $userId,
            'full_name' => $this->strOrNull($r['full_name'] ?? null),
            'phone' => $this->phoneOrNull($r['phone'] ?? null),
            'email' => $this->strOrNull($r['email'] ?? null),
            'customer_type' => $type,
            'governorate' => $this->strOrNull($r['governorate'] ?? null),
            'service' => $this->strOrNull($r['service'] ?? null),
            'notes' => $this->strOrNull($r['notes'] ?? null),
            'assigned_provider_id' => $provider,
            'assigned_provider_name' => $this->strOrNull($r['assigned_provider_name'] ?? null),
            'assigned_at' => $this->ts($r['assigned_at'] ?? null),
            'status' => $status,
            'customer_response' => $response,
            'customer_reason' => $this->strOrNull($r['customer_reason'] ?? null),
            'whats_count' => (int) ($r['whats_count'] ?? 0),
            'calls_count' => (int) ($r['calls_count'] ?? 0),
            'created_at' => $created,
            'updated_at' => $this->ts($r['updated_at'] ?? null) ?? $created,
        ];
    }

    private function mapFavorites(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('favorites', 'fk_user');
        }

        $type = $this->mapUserType($r['type'] ?? null, null);

        if (! in_array($type, ['engineer', 'office', 'exhibition'], true)) {
            return $this->skip('favorites', 'type_invalid');
        }

        $slug = strtolower($this->str($r['slug'] ?? null, ''));
        $key = $userId.'|'.$slug;

        if ($slug === '' || isset($this->seen['favorites'][$key])) {
            return $this->skip('favorites', 'duplicate', false);
        }

        $this->seen['favorites'][$key] = true;
        $created = $this->ts($r['created_at'] ?? null);

        return [
            'id' => (int) $r['id'],
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'image' => $this->strOrNull($r['image'] ?? null),
            'type' => $type,
            'slug' => $slug,
            'phone' => $this->phoneOrNull($r['phone'] ?? null),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapRating(array $r): ?array
    {
        $userId = $r['user_id'] !== null ? (string) $r['user_id'] : null;

        if ($userId !== null && ! $this->userExists($userId)) {
            $userId = null; // كاتب محذوف — نُبقي التقييم (nullOnDelete)
        }

        $rating = (int) ($r['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            return $this->skip('rating', 'rating_invalid');
        }

        $slug = strtolower($this->str($r['slug'] ?? null, ''));

        if ($slug === '') {
            return $this->skip('rating', 'slug_empty');
        }

        // تقييم واحد لكل (user_id, slug) — قرار: UNIQUE في Laravel.
        $key = ($userId ?? '').'|'.$slug;

        if (isset($this->seen['rating'][$key])) {
            return $this->skip('rating', 'duplicate', false);
        }

        $this->seen['rating'][$key] = true;
        $type = $this->mapUserType($r['type'] ?? null, null);
        $allowed = ['customer', 'engineer', 'office', 'exhibition', 'admin'];
        $created = $this->ts($r['created_at'] ?? null);

        // rating.type القديمة ذات دلالة مزدوجة (SPEC 1.2.16) — فُصلت إلى:
        //   target_type: نوع الهدف (platform لتقييمات slug=t3meer).
        //   writer_type: نوع الكاتب (من users.user_type، أو القيمة القديمة
        //                لتقييمات المنصة حيث type = نوع الكاتب).
        $isPlatform = $slug === 't3meer';
        $writerType = $isPlatform && in_array($type, $allowed, true)
            ? $type
            : ($userId !== null ? ($this->userTypes[$userId] ?? null) : null);

        return [
            'id' => (int) $r['id'],
            'slug' => $slug,
            'target_type' => $isPlatform
                ? 'platform'
                : (in_array($type, ['engineer', 'office', 'exhibition'], true) ? $type : null),
            'writer_type' => $writerType,
            'user_id' => $userId,
            'name' => $this->str($r['name'] ?? null, ''),
            'rating' => $rating,
            'message' => $this->strOrNull($r['message'] ?? null),
            'image' => $this->strOrNull($r['image'] ?? null),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    private function mapStats(array $r): ?array
    {
        $metric = strtolower((string) ($r['metric_type'] ?? ''));
        $allowed = ['visit', 'whatsapp', 'call', 'email', 'map', 'facebook', 'instagram', 'youtube'];

        if ($metric === 'phone') {
            $metric = 'call'; // مرادف مقبول في الكود القديم
        }

        if (! in_array($metric, $allowed, true)) {
            return $this->skip('stats', 'metric_invalid');
        }

        $date = (string) ($r['date'] ?? '');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->skip('stats', 'date_invalid');
        }

        $slug = strtolower($this->str($r['slug'] ?? null, ''));

        if ($slug === '') {
            return $this->skip('stats', 'slug_empty');
        }

        // قرار #8: grain يومي — نجمع count لنفس (slug, metric, date).
        $key = $slug.'|'.$metric.'|'.$date;

        if (isset($this->statsAgg[$key])) {
            $this->statsAgg[$key]['count'] += $this->statCount($r);

            return null; // اتجمّع — مش تخطي حقيقي
        }

        $this->statsAgg[$key] = [
            'slug' => $slug,
            'metric_type' => $metric,
            'date' => $date,
            'count' => $this->statCount($r),
            'user_id' => $this->userExists((string) ($r['user_id'] ?? '')) ? (string) $r['user_id'] : null,
            'type' => $this->mapUserType($r['type'] ?? null, null),
            'created_at' => $this->ts($r['created_at'] ?? null) ?? now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($this->dry) {
            $this->report['stats']['imported']++; // في الوضع التجريبي مش هيتكتبوا في flushStats
        }

        return null; // هيتكتب في flushStats()
    }

    private function statCount(array $r): int
    {
        $v = $r['count'] ?? null;

        // العمود ناقص = 1 (صف موجود يعني حدث واحد)؛ أما 0 فيُحترم كما هو.
        if ($v === null || $v === '') {
            return 1;
        }

        return max(0, (int) $v);
    }

    private function mapAnalytics(array $r): ?array
    {
        $type = (string) ($r['stat_type'] ?? '');

        if (! in_array($type, ['page', 'service', 'governorate', 'sort'], true)) {
            return $this->skip('analytics_counts', 'stat_type_invalid');
        }

        $key = $type.'|'.(string) ($r['stat_key'] ?? '');

        if ($r['stat_key'] === null || isset($this->seen['analytics'][$key])) {
            return $this->skip('analytics_counts', 'duplicate', false);
        }

        $this->seen['analytics'][$key] = true;

        return [
            'id' => (int) $r['id'],
            'stat_type' => $type,
            'stat_key' => (string) $r['stat_key'],
            'total_count' => (int) ($r['total_count'] ?? 0),
            'created_at' => $this->ts($r['created_at'] ?? null),
            'updated_at' => $this->ts($r['updated_at'] ?? null),
        ];
    }

    private function mapMarketing(array $r): ?array
    {
        $userId = (string) ($r['user_id'] ?? '');

        if (! $this->userExists($userId)) {
            return $this->skip('marketing', 'fk_user');
        }

        if (isset($this->seen['marketing'][$userId])) {
            return $this->skip('marketing', 'duplicate', false);
        }

        $this->seen['marketing'][$userId] = true;
        $created = $this->ts($r['created_at'] ?? null);

        return [
            'user_id' => $userId,
            'banner' => $this->jsonOrNull($r['banner'] ?? null),
            'meta_pixel' => $this->strOrNull($r['meta_pixel'] ?? null),
            'social' => $this->jsonOrNull($r['social'] ?? null),
            'plan_type' => $this->mapPlanType($r['plan_type'] ?? null, null),
            'user_type' => $this->mapUserType($r['user_type'] ?? null, null),
            'slug' => $this->strOrNull($r['slug'] ?? null),
            'created_at' => $created,
            'updated_at' => $created,
        ];
    }

    // ------------------------------------------------------------------
    // الكتابة
    // ------------------------------------------------------------------

    private function insertRow(string $table, array $row): void
    {
        // stats بتتجمع في الذاكرة وتتكتب دفعة واحدة.
        if ($table === 'stats') {
            return;
        }

        try {
            $affected = DB::table($table)->insertOrIgnore($row);
            $this->report[$table]['imported'] += $affected ? 1 : 0;
            if (! $affected) {
                $this->report[$table]['dup']++;
            }
        } catch (\Throwable $e) {
            $this->report[$table]['skipped']++;
            $this->skip($table, 'db_error:'.$e->getCode(), false);
        }
    }

    private function importPlanPermissionTemplates(string $dir): void
    {
        if ($this->dry || empty($this->planTemplates)) {
            return;
        }

        // idempotent: لو فيه صفوف template أصلاً — متكررش.
        if (DB::table('user_permissions')->whereNull('user_id')->exists()) {
            return;
        }

        foreach (array_chunk($this->planTemplates, 50) as $chunk) {
            DB::table('user_permissions')->insertOrIgnore($chunk);
        }

        $this->report['user_permissions']['source'] ??= ['source' => 0, 'imported' => 0, 'skipped' => 0, 'dup' => 0];
        $this->report['user_permissions']['imported'] += count($this->planTemplates);
        $this->line('  + '.count($this->planTemplates).' صف template للحدود (من plans)');
    }

    private function flushStats(): void
    {
        if ($this->dry || empty($this->statsAgg)) {
            return;
        }

        foreach (array_chunk($this->statsAgg, 200) as $chunk) {
            $affected = DB::table('stats')->insertOrIgnore($chunk);
            $this->report['stats']['imported'] += $affected;
        }

        $this->statsAgg = [];
    }

    private function freshTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(self::ORDER) as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->info('تم مسح الجداول المستهدفة (foreign keys معطّلة مؤقتاً).');
    }

    // ------------------------------------------------------------------
    // أدوات التحويل
    // ------------------------------------------------------------------

    private function mapUserType(mixed $v, ?string $default = 'engineer'): ?string
    {
        if ($v === null || $v === '') {
            return $default;
        }

        $v = strtolower(trim((string) $v));

        // قرار #2: material/supplier → exhibition (مفيش نوع مستقل).
        $v = in_array($v, ['material', 'supplier'], true) ? 'exhibition' : $v;

        $allowed = ['customer', 'engineer', 'office', 'exhibition', 'admin'];

        return in_array($v, $allowed, true) ? $v : $default;
    }

    private function mapPlanType(mixed $v, ?string $default = 'free'): ?string
    {
        if ($v === null || $v === '') {
            return $default;
        }

        $v = strtolower(trim((string) $v));

        if ($v === 'pro') {
            $v = 'professional'; // قرار #1: pro → professional
        }

        $allowed = ['free', 'basic', 'professional', 'premium'];

        return in_array($v, $allowed, true) ? $v : $default;
    }

    private function mapUserStatus(mixed $v): string
    {
        $v = strtolower(trim((string) ($v ?? 'pending')));

        if ($v === 'reject') {
            $v = 'rejected'; // قرار #4
        }

        return in_array($v, ['pending', 'active', 'rejected', 'suspended'], true) ? $v : 'pending';
    }

    private function mapBillingCycle(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        $v = strtolower(trim((string) $v));

        $map = [
            'monthly' => 'monthly', 'month' => 'monthly',
            'quarter' => 'quarter', 'quarterly' => 'quarter', '3months' => 'quarter',
            'half' => 'half', 'half_yearly' => 'half', 'semiannual' => 'half', '6months' => 'half',
            'yearly' => 'yearly', 'year' => 'yearly', 'annual' => 'yearly', 'annually' => 'yearly',
        ];

        return $map[$v] ?? null;
    }

    private function mapPriority(mixed $v): string
    {
        $v = strtolower(trim((string) ($v ?? 'normal'))); // قرار: توحيد case

        return in_array($v, ['normal', 'medium', 'high'], true) ? $v : 'normal';
    }

    /** تحويل timestamp بتوقيت Postgres إلى صيغة MySQL. */
    private function ts(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        $v = (string) $v;
        $v = preg_replace('/\.\d+/', '', $v); // إزالة الكسور
        $v = preg_replace('/[+-]\d{2}(:\d{2})?$/', '', $v); // إزالة المنطقة الزمنية
        $v = trim($v);

        return preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $v)
            ? (strlen($v) === 10 ? $v.' 00:00:00' : $v)
            : null;
    }

    /** تحويل مصفوفة Postgres {a,"b,c"} إلى JSON. */
    private function pgArray(mixed $v): ?string
    {
        if ($v === null || $v === '' || $v === '{}') {
            return null;
        }

        $s = trim((string) $v);

        // القيمة أصلاً JSON (jsonb في المصدر) — نُبقيها كما هي بدون تغليف مزدوج.
        if ($s[0] === '[' || $s[0] === '{') {
            json_decode($s, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $s;
            }

            return null; // JSON تالف
        }

        if ($s[0] !== '{') {
            return json_encode([$s], JSON_UNESCAPED_UNICODE);
        }

        $s = substr($s, 1, -1);

        if ($s === '') {
            return '[]';
        }

        $items = [];
        $buf = '';
        $inQ = false;

        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            $c = $s[$i];

            if ($inQ) {
                if ($c === '"' && ($i === 0 || $s[$i - 1] !== '\\')) {
                    $inQ = false;

                    continue;
                }
                $buf .= $c;

                continue;
            }

            if ($c === '"') {
                $inQ = true;

                continue;
            }

            if ($c === ',') {
                $items[] = trim($buf);
                $buf = '';

                continue;
            }

            $buf .= $c;
        }

        $items[] = trim($buf);

        return json_encode(
            array_values(array_filter($items, fn ($x) => $x !== '')),
            JSON_UNESCAPED_UNICODE
        );
    }

    private function jsonOrNull(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        $s = trim((string) $v);

        // صيغة Postgres ARRAY['a','b'] → مصفوفة JSON (تُستخدم في documents).
        if (preg_match('/^ARRAY\s*\[(.*)\]$/is', $s, $m)) {
            $inner = trim($m[1]);

            if ($inner === '') {
                return '[]';
            }

            $items = [];
            $buf = '';
            $inQ = false;

            for ($i = 0, $n = strlen($inner); $i < $n; $i++) {
                $c = $inner[$i];

                if ($inQ) {
                    if ($c === "'") {
                        if ($i + 1 < $n && $inner[$i + 1] === "'") {
                            $buf .= "'";
                            $i++;

                            continue;
                        }
                        $items[] = $buf;
                        $buf = '';
                        $inQ = false;

                        continue;
                    }
                    $buf .= $c;

                    continue;
                }

                if ($c === "'") {
                    $inQ = true;

                    continue;
                }
            }

            return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (json_decode($s, true) === null && json_last_error() !== JSON_ERROR_NONE) {
            return null; // JSON تالف — نتجاهله بدل ما نكسر الاستيراد
        }

        return $s;
    }

    private function phoneOrNull(mixed $v): ?string
    {
        $p = normalizePhone((string) ($v ?? ''));

        return $p === '' ? null : $p;
    }

    private function numOrNull(mixed $v): ?float
    {
        return $v === null || $v === '' ? null : (float) $v;
    }

    private function intOrNull(mixed $v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    private function str(mixed $v, string $default): string
    {
        return ($v === null || $v === '') ? $default : (string) $v;
    }

    private function strOrNull(mixed $v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function userExists(string $id): bool
    {
        return $id !== '' && isset($this->userIds[$id]);
    }

    private function skip(string $table, string $reason, bool $count = true): null
    {
        if ($count) {
            $this->report[$table]['skipped']++;
            $this->skippedReasons[$table][$reason] = ($this->skippedReasons[$table][$reason] ?? 0) + 1;
        }

        return null;
    }

    private function warnOnce(string $key, string $message): void
    {
        if (! isset($this->warnings[$key])) {
            $this->warnings[$key] = $message;
        }
    }

    // ------------------------------------------------------------------
    // التقرير
    // ------------------------------------------------------------------

    private function printReport(): void
    {
        $this->flushStats();

        $this->newLine();
        $this->info('══════ تقرير ترحيل بيانات البروداكشن ══════');

        $rows = [];

        foreach ($this->report as $table => $r) {
            $rows[] = [$table, $r['source'], $r['imported'], $r['skipped'], $r['dup']];
        }

        $this->table(['الجدول', 'المصدر', 'المُرحّل', 'المتخطّى', 'مكرر'], $rows);

        if ($this->skippedReasons !== []) {
            $this->newLine();
            $this->warn('أسباب التخطي:');

            foreach ($this->skippedReasons as $table => $reasons) {
                foreach ($reasons as $reason => $count) {
                    $this->line("  - {$table}: {$reason} × {$count}");
                }
            }
        }

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn('تحذيرات:');

            foreach ($this->warnings as $msg) {
                $this->line("  ! {$msg}");
            }
        }

        $this->newLine();
        $this->line('  ℹ الحسابات المُرحّلة بدون كلمة مرور — الدخول عبر "نسيت كلمة المرور".');
        $this->line('  ℹ ملفات الـ views الثلاثة اتخطت (بتتبني كاستعلامات Laravel).');
    }
}
