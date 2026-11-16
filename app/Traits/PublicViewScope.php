<?php

namespace App\Traits;

use App\Enums\MetricType;
use App\Enums\PlanType;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * الجواب المشترك لـ query scopes الـ 5 views القديمة في Supabase
 * (SPEC 1.3 + مهمة ClickUp #8).
 *
 * القاعدة نفسها على كل الـ views:
 * - الحسابات النشطة فقط: join جدول users + users.status = 'active' (SPEC 1.8.1).
 * - phone/email/selected_plan من users بأسماء مُعاد تسميتها (بتتعارض مع أعمدة الملف).
 * - average_rating / reviews_count / total_visits = correlated subqueries في الـ select
 *   (مطابقة لتعريف الـ view وبدون تضخّم صفوف بسبب الـ joins).
 * - services/projects/products/permissions = JSON في الـ select:
 *   JSON_ARRAYAGG (MySQL) ↔ JSON_GROUP_ARRAY (SQLite) — نفس الناتج على الاتنين.
 *
 * ⛔ public_materials_view مش هتتعمل (القرار 0.5: المورد = exhibition).
 */
trait PublicViewScope
{
    /**
     * join جدول users + قيد الحسابات النشطة + أعمدة المستخدم في الـ select.
     *
     * ⚠ الـ select هنا بعد `select({table}.*)` عشان العمود المُعاد تسميته
     * يلحق أعمدة الملف في ترتيب النتيجة — PDO بيرجع آخر تكرار للاسم
     * (عند المهندس في phone موجودة في الملف فالتقاطع لازم يكسب فيها).
     *
     * @param  string  $phone  تعبير الموبايل — للمهندسين: COALESCE(الملف، المستخدم)
     *                         لأن الكود القديم ماكانش يكتب phone في الملف (SPEC 5.2.x).
     * @param  bool  $withUserName  يضيف user_name = users.name (للمهندسين فقط).
     */
    protected function joinActiveUsers(Builder $query, string $phone = 'users.phone', bool $withUserName = false): void
    {
        $profile = $query->getModel()->getTable();

        $query->join('users', 'users.id', '=', "{$profile}.user_id")
            ->where('users.status', Status::Active->value)
            ->addSelect([
                DB::raw("{$phone} as phone"),
                DB::raw('users.email as email'),
                DB::raw('users.selected_plan as selected_plan'),
            ]);

        if ($withUserName) {
            $query->addSelect([DB::raw('users.name as user_name')]);
        }
    }

    /**
     * الحسابات المترابطة في الـ view (SPEC 1.3):
     * average_rating = avg(rating.rating) بنفس slug، reviews_count = عددها،
     * total_visits = sum(stats.count) حيث metric_type = 'visit'.
     *
     * ⚠ المقارنة على بيانات البروداكشن الحقيقية (المهمة 10) أثبتت إن الـ view
     * القديم كان بيحسب الزيارات = SUM(count) + COUNT(صفوف visit) — يعني كل صف
     * بيزيد زيارة عن الـ count بتاعه. مستورد Supabase بيحفظ المعنى ده جوه
     * `count` لصفوف visit (قرار #8: صف واحد لكل يوم)، فالـ scope هنا يجمع
     * `count` عادي ويطابق الـ view (20/20 مكتب في ملف التصدير).
     *
     * بدون تقييمات/زيارات ترجع 0 (الواجهة بتعتبرها صفر أصلاً).
     *
     * ⚠ الـ alias لازم يكون جوه DB::raw: مفاتيح addSelect بتتجاهل للـ Expressions.
     */
    protected function withViewAggregates(Builder $query): void
    {
        $profile = $query->getModel()->getTable();
        $visit = MetricType::Visit->value;

        $query->addSelect([
            DB::raw(
                "(SELECT ROUND(COALESCE(AVG(r.rating), 0), 2) FROM rating r WHERE r.slug = {$profile}.slug) as average_rating"
            ),
            DB::raw(
                "(SELECT COUNT(*) FROM rating r WHERE r.slug = {$profile}.slug) as reviews_count"
            ),
            DB::raw(
                "(SELECT COALESCE(SUM(s.count), 0) FROM stats s WHERE s.slug = {$profile}.slug AND s.metric_type = '{$visit}') as total_visits"
            ),
        ]);
    }

    /**
     * المجموعات المضمّنة كـ JSON في الـ select (SPEC 1.3):
     * services = {name, icon, color}، projects/products = صفوفها كاملة،
     * permissions = صف user_permissions أو null لو ما فيش صف للمستخدم.
     *
     * كلها correlated subqueries على user_id = المالك (الملف 1:1 مع users).
     *
     * @param  list<string>  $collections  أي من: services, projects, products, permissions
     */
    protected function withViewCollections(Builder $query, array $collections): void
    {
        $profile = $query->getModel()->getTable();
        $driver = $query->getConnection()->getDriverName();
        $select = [];

        if (in_array('services', $collections, true)) {
            $object = "JSON_OBJECT('name', s.name, 'icon', s.icon, 'color', s.color)";
            $select['services'] = "(SELECT {$this->jsonAgg($driver, $object)} FROM services s WHERE s.user_id = {$profile}.user_id)";
        }

        if (in_array('projects', $collections, true)) {
            $details = $this->jsonColumn($driver, 'p.details');
            $object = "JSON_OBJECT('id', p.id, 'name', p.name, 'location', p.location, 'area', p.area, "
                ."'type', p.type, 'image', p.image, 'slug', p.slug, 'user_type', p.user_type, "
                ."'user_name', p.user_name, 'views', p.views, 'details', {$details}, 'created_at', p.created_at)";
            $select['projects'] = "(SELECT {$this->jsonAgg($driver, $object)} FROM projects p WHERE p.user_id = {$profile}.user_id)";
        }

        if (in_array('products', $collections, true)) {
            $details = $this->jsonColumn($driver, 'q.details');
            $object = "JSON_OBJECT('id', q.id, 'name', q.name, 'category', q.category, "
                ."'sub_category', q.sub_category, 'image', q.image, 'price', q.price, 'details', {$details})";
            $select['products'] = "(SELECT {$this->jsonAgg($driver, $object)} FROM products q WHERE q.user_id = {$profile}.user_id)";
        }

        if (in_array('permissions', $collections, true)) {
            $object = "JSON_OBJECT('max_visits', up.max_visits, 'max_projects', up.max_projects, "
                ."'max_services', up.max_services, 'max_requests', up.max_requests, "
                ."'whatsapp_tracking', up.whatsapp_tracking, 'call_tracking', up.call_tracking, "
                ."'detailed_analytics', up.detailed_analytics, 'video_projects', up.video_projects, "
                ."'google_maps_location', up.google_maps_location, 'directory_ad_banner', up.directory_ad_banner, "
                ."'distinguished_badge', up.distinguished_badge, 'homepage_ad_banner', up.homepage_ad_banner, "
                ."'social_media_promo', up.social_media_promo, 'facebook_pixel', up.facebook_pixel, "
                ."'priority_search', up.priority_search)";
            $select['permissions'] = "(SELECT {$object} FROM user_permissions up WHERE up.user_id = {$profile}.user_id)";
        }

        foreach ($select as $column => $expression) {
            $query->addSelect(DB::raw("{$expression} as {$column}"));
        }
    }

    /**
     * الترتيب بالوزن صراحة بدل ترتيب الـ enum (SPEC 1.8.12):
     * premium 30 / professional 20 / basic 10 / free 0.
     *
     * القيم الغامضة/الفارغة بتساوي free (زي normalizePlanTier في الواجهة).
     * يستدعي بعد أي scope من الـ public views (محتاج join جدول users).
     *
     * @param  string  $direction  'desc' (الافتراضي) أو 'asc'
     */
    public function scopeOrderByPlanWeight(Builder $query, string $direction = 'desc'): Builder
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $cases = implode(' ', array_map(
            static fn (PlanType $plan): string => sprintf("WHEN '%s' THEN %d", $plan->value, $plan->weight()),
            PlanType::cases()
        ));

        return $query->orderByRaw("CASE users.selected_plan {$cases} ELSE 0 END {$direction}");
    }

    /**
     * JSON_ARRAYAGG (MySQL) ↔ JSON_GROUP_ARRAY (SQLite) — نفس الناتج (SPEC 1.3).
     */
    protected function jsonAgg(string $driver, string $expression): string
    {
        return $driver === 'sqlite'
            ? "JSON_GROUP_ARRAY({$expression})"
            : "JSON_ARRAYAGG({$expression})";
    }

    /**
     * عمود JSON داخل JSON_OBJECT: SQLite بينخزّنه كنص عادي فلازم json() عشان
     * ما يتحفّظش كـ string مزوّج؛ MySQL بعمود json بيلصقه كقيمة JSON مباشرة.
     */
    protected function jsonColumn(string $driver, string $column): string
    {
        return $driver === 'sqlite' ? "json({$column})" : $column;
    }
}
