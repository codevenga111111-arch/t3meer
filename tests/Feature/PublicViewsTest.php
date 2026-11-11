<?php

/**
 * اختبارات إعادة بناء الـ 5 views كـ query scopes وAPI Resources
 * (SPEC 1.3 + 1.7.6 + 1.8.12 + مهمة ClickUp #8).
 *
 * بيئة التشغيل: SQLite in-memory (phpunit.xml) = بيئة CI.
 * ⛔ `public_materials_view` مش هتتعمل (القرار 0.5 — المورد = exhibition).
 */

use App\Enums\MetricType;
use App\Enums\PlanType;
use App\Enums\Status;
use App\Http\Resources\MarketingBannerResource;
use App\Http\Resources\PremiumProjectResource;
use App\Http\Resources\PublicEngineerResource;
use App\Http\Resources\PublicExhibitionResource;
use App\Http\Resources\PublicOfficeResource;
use App\Models\Engineer;
use App\Models\Exhibition;
use App\Models\Marketing;
use App\Models\Office;
use App\Models\Product;
use App\Models\Project;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Stat;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------------------
// مساعدين بناء البيانات
// ---------------------------------------------------------------------------

/** مستخدم ب slug فريد (users.slug فريد والـ factory بيعتمد على إيميل فريد). */
function viewUser(array $attributes = []): User
{
    static $sequence = 0;
    $sequence++;

    return User::factory()->create(array_merge([
        'slug' => "view-user-{$sequence}",
        'status' => Status::Active->value,
        'user_type' => 'office',
        'selected_plan' => PlanType::Free->value,
    ], $attributes));
}

function viewOffice(User $user, array $attributes = []): Office
{
    return Office::create(array_merge([
        'user_id' => $user->id,
        'name' => 'مكتب الاختبار',
        'slug' => $user->slug,
        'governorate' => 'القاهرة',
    ], $attributes));
}

function viewEngineer(User $user, array $attributes = []): Engineer
{
    return Engineer::create(array_merge([
        'user_id' => $user->id,
        'name' => 'مهندس الاختبار',
        'slug' => $user->slug,
        'governorate' => 'القاهرة',
    ], $attributes));
}

function viewExhibition(User $user, array $attributes = []): Exhibition
{
    return Exhibition::create(array_merge([
        'user_id' => $user->id,
        'name' => 'معرض الاختبار',
        'slug' => $user->slug,
        'governorate' => 'الجيزة',
    ], $attributes));
}

/** مشروع بـ created_at مضبوط يدوياً (fillable ما بياخدهش). */
function viewProject(User $user, array $attributes = [], int $minutesAgo = 0): Project
{
    $project = Project::create(array_merge([
        'user_id' => $user->id,
        'name' => 'مشروع',
        'slug' => $user->slug,
        'user_type' => $user->user_type->value,
        'user_name' => $user->name,
    ], $attributes));

    if ($minutesAgo > 0) {
        DB::table('projects')->where('id', $project->id)->update([
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    return $project->refresh();
}

/** تقييم لـ slug هدف — كل كاتب يقيّم مرة واحدة (UNIQUE(user_id, slug)). */
function viewRating(string $slug, int $stars, ?string $targetType = 'office'): void
{
    $writer = viewUser(['user_type' => 'customer']);

    Rating::create([
        'slug' => $slug,
        'target_type' => $targetType,
        'writer_type' => 'customer',
        'user_id' => $writer->id,
        'name' => $writer->name,
        'rating' => $stars,
    ]);
}

/** صف stats يومي — UNIQUE(slug, metric_type, date) فالتاريخ بيفرق. */
function viewStat(string $slug, string $metric, int $count, int $daysAgo): void
{
    Stat::create([
        'slug' => $slug,
        'metric_type' => $metric,
        'date' => now()->subDays($daysAgo)->toDateString(),
        'count' => $count,
    ]);
}

/** فك عمود JSON اللي جاي من الـ select (MySQL بيرجع string وSQLite كمان). */
function viewJson(mixed $value): array
{
    if (is_array($value)) {
        return $value;
    }

    if (! is_string($value) || $value === '') {
        return [];
    }

    $decoded = json_decode($value, true);

    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
}

// ---------------------------------------------------------------------------
// public_offices_view
// ---------------------------------------------------------------------------

it('returns only active accounts from the offices view', function () {
    $active = viewUser(['selected_plan' => PlanType::Premium->value]);
    viewOffice($active, ['slug' => $active->slug]);

    foreach (['pending', 'rejected', 'suspended'] as $status) {
        $user = viewUser(['status' => $status]);
        viewOffice($user, ['slug' => $user->slug]);
    }

    $rows = Office::query()->publicOfficesView()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->user_id)->toBe($active->id);
});

it('computes average rating, reviews count and total visits for an office', function () {
    $user = viewUser();
    viewOffice($user, ['slug' => $user->slug]);

    viewRating($user->slug, 5);
    viewRating($user->slug, 4);

    viewStat($user->slug, MetricType::Visit->value, 10, 2);
    viewStat($user->slug, MetricType::Visit->value, 5, 1);
    viewStat($user->slug, MetricType::Whatsapp->value, 99, 1); // مش زيارة

    $row = Office::query()->publicOfficesView()->first();

    expect((float) $row->average_rating)->toBe(4.5)
        ->and((int) $row->reviews_count)->toBe(2)
        ->and((int) $row->total_visits)->toBe(15); // 10 + 5 فقط
});

it('returns zero aggregates when the profile has no ratings or visits', function () {
    $user = viewUser();
    viewOffice($user, ['slug' => $user->slug]);

    $row = Office::query()->publicOfficesView()->first();

    expect((float) $row->average_rating)->toBe(0.0)
        ->and((int) $row->reviews_count)->toBe(0)
        ->and((int) $row->total_visits)->toBe(0);
});

it('embeds services, projects and permissions as JSON for an office', function () {
    $user = viewUser(['selected_plan' => PlanType::Premium->value]);
    viewOffice($user, ['slug' => $user->slug]);

    Service::create([
        'user_id' => $user->id, 'name' => 'تصميم معماري',
        'icon' => 'material-symbols:architecture', 'color' => '#38bdf8', 'slug' => $user->slug,
    ]);
    Service::create([
        'user_id' => $user->id, 'name' => 'إدارة مشاريع',
        'icon' => 'mdi:eye-outline', 'color' => '#22c55e', 'slug' => $user->slug,
    ]);

    viewProject($user, ['name' => 'برج سكني']);

    UserPermission::create([
        'user_id' => $user->id,
        'user_type' => 'office',
        'plan_type' => 'premium',
        'max_projects' => -1,
        'whatsapp_tracking' => true,
        'detailed_analytics' => 1,
        'priority_search' => 'high',
    ]);

    $row = Office::query()->publicOfficesView()->first();
    $services = viewJson($row->services);
    $projects = viewJson($row->projects);
    $permissions = viewJson($row->permissions);

    expect($services)->toHaveCount(2)
        ->and($services[0])->toHaveKeys(['name', 'icon', 'color'])
        ->and($services[0]['name'])->toBe('تصميم معماري')
        ->and($projects)->toHaveCount(1)
        ->and($projects[0]['name'])->toBe('برج سكني')
        ->and($permissions['max_projects'])->toBe(-1)
        ->and($permissions['priority_search'])->toBe('high');
});

it('keeps permissions null when the account has no user_permissions row', function () {
    // صف template بـ user_id = null (قرار #6) ما ينفعش يتسند للحساب.
    UserPermission::create([
        'user_id' => null, 'user_type' => 'office', 'plan_type' => 'premium',
    ]);

    $user = viewUser();
    viewOffice($user, ['slug' => $user->slug]);

    $row = Office::query()->publicOfficesView()->first();

    expect($row->permissions)->toBeNull();
});

it('normalizes empty collections through the public office resource', function () {
    $user = viewUser();
    viewOffice($user, ['slug' => $user->slug]);

    $data = (new PublicOfficeResource(
        Office::query()->publicOfficesView()->first()
    ))->resolve(request());

    expect($data['services'])->toBe([])
        ->and($data['projects'])->toBe([])
        ->and($data['permissions'])->toBeNull();
});

// ---------------------------------------------------------------------------
// public_engineers_view
// ---------------------------------------------------------------------------

it('prefers the engineer phone and exposes the account name', function () {
    $fallback = viewUser(['user_type' => 'engineer', 'name' => 'حساب بدون رقم ملف']);
    viewEngineer($fallback, ['slug' => $fallback->slug, 'phone' => null]);

    $own = viewUser(['user_type' => 'engineer', 'name' => 'حساب برقم ملف']);
    viewEngineer($own, ['slug' => $own->slug, 'phone' => '01111111111']);

    $rows = Engineer::query()->publicEngineersView()->get()->keyBy('user_id');

    // COALESCE(engineers.phone, users.phone) — الملف أولاً ثم الحساب.
    expect($rows[$fallback->id]->phone)->toBe($fallback->phone)
        ->and($rows[$own->id]->phone)->toBe('01111111111')
        ->and($rows[$fallback->id]->user_name)->toBe('حساب بدون رقم ملف')
        ->and($rows[$own->id]->user_name)->toBe('حساب برقم ملف');
});

// ---------------------------------------------------------------------------
// public_exhibitions_view
// ---------------------------------------------------------------------------

it('exhibits products and counts projects from the embedded array', function () {
    $user = viewUser(['user_type' => 'exhibition']);
    viewExhibition($user, ['slug' => $user->slug]);

    Product::create([
        'user_id' => $user->id, 'name' => 'طوب أحمر', 'category' => 'مواد البناء الأساسية',
        'sub_category' => 'طوب', 'price' => 120.5, 'slug' => $user->slug,
        'details' => ['description' => 'للبناء'],
    ]);
    Product::create([
        'user_id' => $user->id, 'name' => 'أسمنت', 'category' => 'مواد البناء الأساسية',
        'sub_category' => 'أسمنت', 'price' => 90, 'slug' => $user->slug,
        'details' => null,
    ]);

    viewProject($user, ['name' => 'مشروع معرض 1']);
    viewProject($user, ['name' => 'مشروع معرض 2']);

    $row = Exhibition::query()->publicExhibitionsView()->first();
    $data = (new PublicExhibitionResource($row))->resolve(request());

    $products = viewJson($row->products);
    $cement = collect($products)->firstWhere('name', 'أسمنت');

    expect($products)->toHaveCount(2)
        ->and($cement['details'])->toBeNull()
        ->and($data['count_of_projects'])->toBe(2)
        ->and($data['phone'])->toBe($user->phone)
        ->and($data['products'])->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// الترتيب بالوزن (SPEC 1.8.12)
// ---------------------------------------------------------------------------

it('orders the directory by explicit plan weight', function () {
    foreach (['free', 'basic', 'professional', 'premium'] as $plan) {
        $user = viewUser(['selected_plan' => $plan]);
        viewOffice($user, ['slug' => $user->slug, 'name' => "مكتب {$plan}"]);
    }

    $desc = Office::query()->publicOfficesView()->orderByPlanWeight()->get()->pluck('name');
    $asc = Office::query()->publicOfficesView()->orderByPlanWeight('asc')->get()->pluck('name');

    expect($desc->all())->toBe([
        'مكتب premium', 'مكتب professional', 'مكتب basic', 'مكتب free',
    ])->and($asc->all())->toBe([
        'مكتب free', 'مكتب basic', 'مكتب professional', 'مكتب premium',
    ]);
});

it('does not build the dropped public materials view', function () {
    foreach ([Office::class, Engineer::class, Exhibition::class, Project::class, Marketing::class] as $model) {
        expect(method_exists($model, 'scopePublicMaterialsView'))->toBeFalse();
    }
});

// ---------------------------------------------------------------------------
// premium_active_view + onePerUser (SPEC 1.7.6)
// ---------------------------------------------------------------------------

it('returns only premium active projects newest first', function () {
    $premium = viewUser(['selected_plan' => PlanType::Premium->value]);
    $basic = viewUser(['selected_plan' => PlanType::Basic->value]);
    $suspended = viewUser(['selected_plan' => PlanType::Premium->value, 'status' => Status::Suspended->value]);

    viewProject($premium, ['name' => 'الأقدم'], 120);
    viewProject($premium, ['name' => 'الأحدث'], 10);
    viewProject($basic, ['name' => 'مشروع باقة أساسية'], 5);
    viewProject($suspended, ['name' => 'مشروع موقوف'], 1);

    $names = Project::query()->premiumActiveView()->get()->pluck('name');

    expect($names->all())->toBe(['الأحدث', 'الأقدم']);
});

it('keeps the newest project per user inside the window', function () {
    $a = viewUser(['selected_plan' => PlanType::Premium->value]);
    $b = viewUser(['selected_plan' => PlanType::Premium->value]);

    viewProject($a, ['name' => 'a-old'], 60);
    viewProject($a, ['name' => 'a-mid'], 50);
    viewProject($a, ['name' => 'a-new'], 40);
    viewProject($b, ['name' => 'b-old'], 30);
    viewProject($b, ['name' => 'b-new'], 20);

    // آخر 3 فقط: b-new, b-old, a-new → واحدة لكل مستخدم.
    $names = Project::query()
        ->premiumActiveView()
        ->onePerUser(take: 10, window: 3)
        ->get()
        ->pluck('name');

    expect($names->all())->toBe(['b-new', 'a-new']);
});

it('caps the homepage feed at the take limit', function () {
    $users = collect(range(1, 8))->map(fn () => viewUser([
        'selected_plan' => PlanType::Premium->value,
    ]));

    $users->each(function (User $user, int $index) {
        viewProject($user, ['name' => "p{$index}"], 10 + $index);
    });

    $rows = Project::query()->premiumActiveView()->onePerUser(take: 5, window: 100)->get();

    expect($rows)->toHaveCount(5)
        ->and($rows->first()->name)->toBe('p0')
        ->and($rows->last()->name)->toBe('p4');
});

// ---------------------------------------------------------------------------
// marketing_active_users (SPEC 1.3 + 5.2.10)
// ---------------------------------------------------------------------------

it('returns marketing banners for active accounts with a banner', function () {
    $withBanner = viewUser(['user_type' => 'office']);
    Marketing::create([
        'user_id' => $withBanner->id, 'slug' => $withBanner->slug, 'user_type' => 'office',
        'plan_type' => 'professional',
        'banner' => ['title' => 'عرض', 'description' => 'وصف', 'image' => 'https://cdn/x.jpg'],
    ]);

    $withoutBanner = viewUser(['user_type' => 'office']);
    Marketing::create([
        'user_id' => $withoutBanner->id, 'slug' => $withoutBanner->slug, 'user_type' => 'office',
        'plan_type' => 'professional', 'banner' => null,
    ]);

    $inactive = viewUser(['user_type' => 'office', 'status' => Status::Suspended->value]);
    Marketing::create([
        'user_id' => $inactive->id, 'slug' => $inactive->slug, 'user_type' => 'office',
        'plan_type' => 'professional',
        'banner' => ['title' => 'موقوف', 'image' => 'https://cdn/y.jpg'],
    ]);

    $rows = Marketing::query()->marketingActiveUsers()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->user_id)->toBe($withBanner->id);
});

// ---------------------------------------------------------------------------
// API Resources
// ---------------------------------------------------------------------------

it('serializes an office row through the public office resource', function () {
    $user = viewUser(['selected_plan' => PlanType::Premium->value, 'name' => 'صاحب المكتب']);
    viewOffice($user, [
        'slug' => $user->slug, 'name' => 'مكتب النخبة', 'governorate' => 'الجيزة',
        'experience_years' => 7, 'count_of_projects' => 4,
    ]);

    viewRating($user->slug, 5);
    Service::create([
        'user_id' => $user->id, 'name' => 'تصميم معماري',
        'icon' => 'material-symbols:architecture', 'color' => '#38bdf8', 'slug' => $user->slug,
    ]);
    viewProject($user, ['name' => 'مشروع مدمج']);

    UserPermission::create([
        'user_id' => $user->id, 'user_type' => 'office', 'plan_type' => 'premium',
        'max_projects' => -1, 'whatsapp_tracking' => true,
        'detailed_analytics' => 1, 'video_projects' => false,
        'priority_search' => 'high',
    ]);

    $data = (new PublicOfficeResource(
        Office::query()->publicOfficesView()->first()
    ))->resolve(request());

    expect($data)->toHaveKeys([
        'slug', 'name', 'phone', 'email', 'governorate', 'average_rating',
        'reviews_count', 'total_visits', 'selected_plan', 'is_premium',
        'services', 'projects', 'permissions', 'count_of_projects',
    ])
        ->and($data['slug'])->toBe($user->slug)
        ->and($data['name'])->toBe('مكتب النخبة')
        ->and($data['email'])->toBe($user->email)
        ->and($data['phone'])->toBe($user->phone)
        ->and($data['selected_plan'])->toBe('premium')
        ->and($data['is_premium'])->toBeTrue()
        ->and($data['average_rating'])->toBeFloat()->toBe(5.0)
        ->and($data['reviews_count'])->toBe(1)
        ->and($data['count_of_projects'])->toBe(4) // عمود يدوي
        ->and($data['services'])->toHaveCount(1)
        ->and($data['services'][0]['name'])->toBe('تصميم معماري')
        ->and($data['projects'])->toHaveCount(1)
        // الأعلام لازم boolean حقيقي (الواجهة بتقارن بـ === false).
        ->and($data['permissions']['whatsapp_tracking'])->toBeTrue()
        ->and($data['permissions']['detailed_analytics'])->toBeTrue()
        ->and($data['permissions']['video_projects'])->toBeFalse()
        ->and($data['permissions']['priority_search'])->toBe('high')
        ->and($data['permissions']['max_projects'])->toBe(-1)
        // views_count اتشال لأنه مرادف total_visits.
        ->and(array_key_exists('views_count', $data))->toBeFalse();
});

it('serializes an engineer row with account name and skills', function () {
    $user = viewUser([
        'user_type' => 'engineer', 'name' => 'حساب المهندس',
        'selected_plan' => PlanType::Professional->value,
    ]);
    viewEngineer($user, [
        'slug' => $user->slug, 'name' => 'م. أحمد', 'phone' => null,
        'major' => 'معماري', 'city' => 'القاهرة', 'website' => 'https://ahmed.test',
        'skills' => ['تصميم معماري', 'حصر كميات'],
    ]);

    $data = (new PublicEngineerResource(
        Engineer::query()->publicEngineersView()->first()
    ))->resolve(request());

    expect($data['slug'])->toBe($user->slug)
        ->and($data['name'])->toBe('م. أحمد')
        ->and($data['user_name'])->toBe('حساب المهندس')
        ->and($data['phone'])->toBe($user->phone)
        ->and($data['email'])->toBe($user->email)
        ->and($data['website'])->toBe('https://ahmed.test')
        ->and($data['skills'])->toBe(['تصميم معماري', 'حصر كميات'])
        ->and($data['selected_plan'])->toBe('professional')
        ->and($data['is_premium'])->toBeFalse();
});

it('counts projects from the embedded array in the exhibition resource', function () {
    $user = viewUser(['user_type' => 'exhibition', 'selected_plan' => PlanType::Premium->value]);
    viewExhibition($user, ['slug' => $user->slug, 'name' => 'معرض المواد', 'count_of_categories' => 3]);

    viewProject($user, ['name' => 'معرض-مشروع 1']);
    viewProject($user, ['name' => 'معرض-مشروع 2']);
    viewProject($user, ['name' => 'معرض-مشروع 3']);

    $data = (new PublicExhibitionResource(
        Exhibition::query()->publicExhibitionsView()->first()
    ))->resolve(request());

    expect($data['name'])->toBe('معرض المواد')
        ->and($data['count_of_projects'])->toBe(3)
        ->and($data['projects'])->toHaveCount(3)
        ->and($data['is_premium'])->toBeTrue()
        ->and($data['count_of_categories'])->toBe(3);
});

it('serializes a premium project with its details', function () {
    $user = viewUser(['user_type' => 'engineer', 'selected_plan' => PlanType::Premium->value]);
    $project = viewProject($user, [
        'name' => 'فيلا ساحلية', 'location' => 'الساحل الشمالي', 'area' => 250.5,
        'type' => 'سكني', 'user_type' => 'engineer', 'user_name' => 'م. سارة',
        'details' => ['brief' => 'وصف المشروع', 'photos' => ['a.jpg']],
    ]);

    $data = (new PremiumProjectResource(
        Project::query()->premiumActiveView()->first()
    ))->resolve(request());

    expect($data['id'])->toBe($project->id)
        ->and($data['user_id'])->toBe($user->id)
        ->and($data['name'])->toBe('فيلا ساحلية')
        ->and($data['location'])->toBe('الساحل الشمالي')
        ->and($data['user_name'])->toBe('م. سارة')
        ->and($data['details'])->toBe(['brief' => 'وصف المشروع', 'photos' => ['a.jpg']])
        ->and($data['created_at'])->not->toBeNull();
});

it('serializes a marketing banner', function () {
    $user = viewUser(['user_type' => 'office', 'selected_plan' => PlanType::Premium->value]);
    Marketing::create([
        'user_id' => $user->id, 'slug' => $user->slug, 'user_type' => 'office',
        'plan_type' => 'premium',
        'banner' => ['title' => 'بانر', 'description' => 'وصف', 'image' => 'https://cdn/b.jpg'],
    ]);

    $data = (new MarketingBannerResource(
        Marketing::query()->marketingActiveUsers()->first()
    ))->resolve(request());

    expect($data)->toBe([
        'banner' => ['title' => 'بانر', 'description' => 'وصف', 'image' => 'https://cdn/b.jpg'],
        'slug' => $user->slug,
        'user_type' => 'office',
        'plan_type' => 'premium',
    ]);
});
