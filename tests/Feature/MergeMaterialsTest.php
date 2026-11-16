<?php

/**
 * اختبار أمر دمج الموردين (material/supplier) في exhibition
 * (القرار 0.5 + مهمة ClickUp #10 — SPEC 0.5 «نقل البيانات»).
 *
 * البيئة: اتصال SQLite منفصل (legacy_merge) بـ schema قديم مبني داخل الاختبار
 * — الـ schema الجديد ما يقبلش قيمة material أصلاً (enum/CHECK — قرار 7.1#2)،
 * وعشان كده للأمر خيار `--database=`: يشتغل على القاعدة القديمة كما هي.
 *
 * المرجع: T3MEER_BACKEND_API_SPEC_v3.md 0.5 (الترتيب + خريطة الأعمدة)
 * و T3MER_CLICKUP_PLAN.md اليوم 3 — التاسكة 10.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// مساعدات بناء القاعدة القديمة
// ---------------------------------------------------------------------------

/** اسم الاتصال المنفصل (schema قديم بلا قيود enum). */
function mergeLegacyConnection(): string
{
    return 'legacy_merge';
}

/**
 * الاتصال الأصلي (اللي بيبني عليه RefreshDatabase ترانزاكشن كل test) —
 * الأمر بيدوّر default على اتصال القاعدة القديمة، ولازم يترجع قبل teardown
 * وإلا تبانك ترانزاكشن الاختبار مفتوحة على اتصال غلط.
 */
function mergeRememberDefault(?string $value = null): ?string
{
    static $default = null;

    if ($value !== null) {
        $default = $value;
    }

    return $default;
}

/** هل الـ view القديم موجود؟ (Schema::hasTable ما بتشوفش الـ views في SQLite). */
function mergeViewExists(): bool
{
    $views = DB::connection(mergeLegacyConnection())
        ->select("SELECT name FROM sqlite_master WHERE type = 'view' AND name = 'public_materials_view'");

    return $views !== [];
}

/** القاعدة القديمة: جداولها كلها أعمدة نصية/عادية بلا enum. */
function mergeCreateLegacySchema(): void
{
    $schema = Schema::connection(mergeLegacyConnection());

    $schema->create('users', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('email');
        $table->string('password')->nullable();
        $table->string('name')->nullable();
        $table->string('slug')->nullable();
        $table->string('phone')->nullable();
        $table->string('user_type')->nullable();
        $table->string('selected_user_type')->nullable();
        $table->string('selected_plan')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    $schema->create('materials', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('user_id');
        $table->string('name')->nullable();
        $table->string('governorate')->nullable();
        $table->string('city')->nullable();
        $table->text('bio')->nullable();
        $table->string('whats_app')->nullable();
        $table->string('open_hours')->nullable();
        $table->string('main_category')->nullable();
        $table->integer('experience_years')->nullable();
        $table->integer('count_of_categories')->nullable();
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->text('profile_image')->nullable();
        $table->text('specializations')->nullable();
        // حقول المورد الخاصة — تتشال حسب القرار 0.5.3 (مش موجودة في المعرض).
        $table->text('price_list')->nullable();
        $table->text('delivery_areas')->nullable();
        $table->timestamps();
    });

    $schema->create('exhibitions', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('user_id');
        $table->string('name')->nullable();
        $table->string('slug')->nullable();
        $table->string('main_category')->nullable();
        $table->string('governorate')->nullable();
        $table->string('city')->nullable();
        $table->text('bio')->nullable();
        $table->string('whats_app')->nullable();
        $table->integer('experience_years')->default(0);
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->string('logo', 500)->nullable();
        $table->string('profile_image', 500)->nullable();
        $table->unsignedSmallInteger('count_of_categories')->nullable();
        $table->string('open_hours')->nullable();
        $table->timestamps();
    });

    $schema->create('services', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->string('name');
        $table->string('icon')->nullable();
        $table->string('color')->default('#0070d7');
        $table->string('slug');
        $table->timestamps();
    });

    $schema->create('favorites', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->string('name');
        $table->string('type')->nullable();
        $table->string('slug');
        $table->timestamps();
    });

    $schema->create('rating', function (Blueprint $table) {
        $table->id();
        $table->string('slug');
        $table->string('type')->nullable(); // الدلالة المزدوجة القديمة (مهمة #5)
        $table->string('user_id')->nullable();
        $table->string('name');
        $table->unsignedTinyInteger('rating');
        $table->text('message')->nullable();
        $table->timestamps();
    });

    $schema->create('stats', function (Blueprint $table) {
        $table->id();
        $table->string('slug');
        $table->string('metric_type');
        $table->date('date');
        $table->unsignedInteger('count')->default(1);
        $table->string('type')->nullable();
        $table->timestamps();
    });

    $schema->create('user_validation', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('user_id');
        $table->string('name');
        $table->string('account_type')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    $schema->create('plans', function (Blueprint $table) {
        $table->id();
        $table->string('user_type');
        $table->string('plan_type');
        $table->string('billing_cycle');
        $table->decimal('price', 10, 2)->default(0);
        $table->timestamps();
    });

    $schema->create('subscriptions', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('user_id');
        $table->string('plan_type');
        $table->string('user_type')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    $schema->create('marketing', function (Blueprint $table) {
        $table->string('user_id', 36)->primary();
        $table->string('user_type')->nullable();
        $table->string('slug')->nullable();
        $table->timestamps();
    });

    $schema->create('projects', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('user_id');
        $table->string('name');
        $table->string('slug');
        $table->string('user_type')->nullable();
        $table->timestamps();
    });

    $schema->create('user_permissions', function (Blueprint $table) {
        $table->id();
        $table->string('user_id')->nullable();
        $table->string('user_type')->nullable();
        $table->string('plan_type');
        $table->integer('max_visits')->nullable();
        $table->timestamps();
    });

    $schema->create('analytics_counts', function (Blueprint $table) {
        $table->id();
        $table->string('stat_type');
        $table->string('stat_key');
        $table->unsignedBigInteger('total_count')->default(0);
        $table->timestamps();
    });

    // public_materials_view القديم — يتشال في خطوة 6 (0.5.6).
    DB::connection(mergeLegacyConnection())->statement(
        'CREATE VIEW public_materials_view AS
         SELECT m.name AS material_name, u.slug AS slug, m.governorate AS governorate
         FROM materials m JOIN users u ON u.id = m.user_id'
    );
}

/** بيانات legacy: مستخدمان قديمان (material/supplier) + مكتب للتحكم. */
function mergeSeedLegacyData(): void
{
    $db = DB::connection(mergeLegacyConnection());
    $now = now()->format('Y-m-d H:i:s');

    foreach ([
        ['mat-1', 'mat1@example.com', 'material', 'mat-one', 'مورد الحديد', 'material'],
        ['sup-1', 'sup1@example.com', 'supplier', 'sup-one', 'مورد الأسمنت', null],
        ['off-1', 'off1@example.com', 'office', 'office-one', 'مكتب الاختبار', null],
    ] as [$id, $email, $type, $slug, $name, $selected]) {
        $db->table('users')->insert([
            'id' => $id, 'email' => $email, 'name' => $name, 'slug' => $slug,
            'user_type' => $type, 'selected_user_type' => $selected,
            'selected_plan' => 'basic', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // ملف المورد القديم (SPEC 1.2.5) + حقل خاص لا ينتقل (قرار 0.5.3).
    $db->table('materials')->insert([
        'id' => 'mat-row-1', 'user_id' => 'mat-1', 'name' => 'مورد الحديد',
        'governorate' => 'القاهرة', 'city' => 'شبرا', 'bio' => 'توريد مواد بناء',
        'whats_app' => '01012345678', 'open_hours' => '9ص-5م',
        'main_category' => 'مواد بناء', 'experience_years' => 5,
        'count_of_categories' => 3, 'latitude' => 30.1234567, 'longitude' => 31.2345678,
        'profile_image' => 'https://res.cloudinary.com/x/materials/profiles/a.webp',
        'specializations' => '["site-delivery","wholesale-prices","payment-plans","توصيل خاص"]',
        'price_list' => 'خصم 10%', 'delivery_areas' => 'القاهرة والجيزة',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    // خدمة مكتوبة بالlabel القديم للمورد التاني (لازم تتوحد).
    $db->table('services')->insert([
        'user_id' => 'sup-1', 'name' => 'توصيل للموقع',
        'icon' => 'solar:double-alt-arrow-right-bold', 'color' => '#FC7753',
        'slug' => 'sup-one', 'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('favorites')->insert([
        'user_id' => 'off-1', 'name' => 'مورد الحديد', 'type' => 'material',
        'slug' => 'mat-one', 'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('rating')->insert([
        'slug' => 'mat-one', 'type' => 'material', 'user_id' => 'off-1',
        'name' => 'مكتب الاختبار', 'rating' => 5,
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('stats')->insert([
        'slug' => 'mat-one', 'metric_type' => 'visit',
        'date' => now()->format('Y-m-d'), 'count' => 3, 'type' => 'material',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('user_validation')->insert([
        'id' => 'val-1', 'user_id' => 'mat-1', 'name' => 'مورد الحديد',
        'account_type' => 'material', 'status' => 'pending',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    foreach ([['material', 100], ['exhibition', 120]] as [$userType, $price]) {
        $db->table('plans')->insert([
            'user_type' => $userType, 'plan_type' => 'basic',
            'billing_cycle' => 'monthly', 'price' => $price,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // صف template (user_id null) + صف مستخدم حقيقي (user_id mat-1).
    $db->table('user_permissions')->insert([
        ['user_id' => null, 'user_type' => 'material', 'plan_type' => 'basic',
            'max_visits' => null, 'created_at' => $now, 'updated_at' => $now],
        ['user_id' => 'mat-1', 'user_type' => 'material', 'plan_type' => 'professional',
            'max_visits' => 500, 'created_at' => $now, 'updated_at' => $now],
    ]);

    $db->table('marketing')->insert([
        'user_id' => 'mat-1', 'user_type' => 'material', 'slug' => 'mat-one',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('subscriptions')->insert([
        'id' => 'sub-1', 'user_id' => 'mat-1', 'plan_type' => 'basic',
        'user_type' => 'material', 'status' => 'active',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $db->table('projects')->insert([
        'id' => 'prj-1', 'user_id' => 'mat-1', 'name' => 'مشروع مورد',
        'slug' => 'mat-one', 'user_type' => 'material',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    foreach ([['materials', 5], ['showrooms-suppliers', 7], ['engineers', 11]] as [$key, $total]) {
        $db->table('analytics_counts')->insert([
            'stat_type' => 'page', 'stat_key' => $key, 'total_count' => $total,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}

/** تنفيذ الأمر على القاعدة القديمة. */
function mergeRun(array $options = []): int
{
    return Artisan::call('t3meer:merge-materials', array_merge(
        ['--database' => mergeLegacyConnection()],
        $options
    ));
}

beforeEach(function () {
    mergeRememberDefault(config('database.default'));

    Config::set('database.connections.'.mergeLegacyConnection(), [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    mergeCreateLegacySchema();
    mergeSeedLegacyData();
});

afterEach(function () {
    if ($default = mergeRememberDefault()) {
        config(['database.default' => $default]);
    }
});

// ---------------------------------------------------------------------------
// الاختبارات
// ---------------------------------------------------------------------------

it('writes nothing in dry mode', function () {
    expect(mergeRun(['--dry' => true]))->toBe(0);

    $db = DB::connection(mergeLegacyConnection());

    expect($db->table('users')->where('id', 'mat-1')->value('user_type'))->toBe('material')
        ->and($db->table('exhibitions')->count())->toBe(0)
        ->and($db->table('services')->value('name'))->toBe('توصيل للموقع')
        ->and((int) $db->table('analytics_counts')->where('stat_key', 'showrooms-suppliers')->value('total_count'))->toBe(7)
        ->and(Schema::connection(mergeLegacyConnection())->hasTable('materials'))->toBeTrue()
        ->and(mergeViewExists())->toBeTrue();
});

it('converts material and supplier users to exhibition keeping slugs', function () {
    expect(mergeRun())->toBe(0);

    $user = fn (string $id, string $column): mixed => DB::connection(mergeLegacyConnection())
        ->table('users')->where('id', $id)->value($column);

    expect($user('mat-1', 'user_type'))->toBe('exhibition')
        ->and($user('sup-1', 'user_type'))->toBe('exhibition')
        ->and($user('off-1', 'user_type'))->toBe('office')
        ->and($user('mat-1', 'slug'))->toBe('mat-one')
        ->and($user('mat-1', 'selected_user_type'))->toBe('exhibition');
});

it('copies materials into exhibitions with unified services', function () {
    expect(mergeRun())->toBe(0);

    $db = DB::connection(mergeLegacyConnection());
    $exhibition = $db->table('exhibitions')->where('user_id', 'mat-1')->first();

    expect($exhibition)->not->toBeNull()
        ->and($exhibition->name)->toBe('مورد الحديد')
        ->and($exhibition->slug)->toBe('mat-one')
        ->and($exhibition->governorate)->toBe('القاهرة')
        ->and($exhibition->city)->toBe('شبرا')
        ->and($exhibition->main_category)->toBe('مواد بناء')
        ->and((int) $exhibition->experience_years)->toBe(5)
        ->and((int) $exhibition->count_of_categories)->toBe(3)
        ->and($exhibition->whats_app)->toBe('01012345678')
        ->and($exhibition->open_hours)->toBe('9ص-5م')
        ->and($exhibition->bio)->toBe('توريد مواد بناء')
        ->and(abs((float) $exhibition->latitude - 30.1234567))->toBeLessThan(0.000001)
        // profile_image → profile_image و logo (SPEC 0.5.2).
        ->and($exhibition->profile_image)->toContain('materials/profiles/a.webp')
        ->and($exhibition->logo)->toBe($exhibition->profile_image);

    $names = $db->table('services')->where('user_id', 'mat-1')
        ->orderBy('name')->pluck('name')->all();

    // site-delivery→توصيل وشحن + wholesale-prices→بيع بالجملة + payment-plans
    // + قيمة مجهولة تبقى custom (ما تترميش).
    expect($names)->toEqualCanonicalizing(['توصيل خاص', 'توصيل وشحن', 'تسهيلات سداد', 'بيع بالجملة'])
        ->and($db->table('services')->where('user_id', 'mat-1')->count())->toBe(4);

    // كلها بأيقونة/لون المعرض و slug المالك.
    $service = $db->table('services')->where('user_id', 'mat-1')->where('name', 'توصيل وشحن')->first();
    expect($service->icon)->toBe('solar:double-alt-arrow-right-bold')
        ->and($service->color)->toBe('#FC7753')
        ->and($service->slug)->toBe('mat-one');

    // service المورد التاني المكتوبة بالlabel القديم اتوحدت.
    expect($db->table('services')->where('user_id', 'sup-1')->value('name'))->toBe('توصيل وشحن');
});

it('maps every legacy type column to exhibition', function () {
    expect(mergeRun())->toBe(0);

    $db = DB::connection(mergeLegacyConnection());

    expect($db->table('favorites')->value('type'))->toBe('exhibition')
        ->and($db->table('rating')->value('type'))->toBe('exhibition')
        ->and($db->table('stats')->value('type'))->toBe('exhibition')
        ->and($db->table('user_validation')->value('account_type'))->toBe('exhibition')
        ->and($db->table('marketing')->value('user_type'))->toBe('exhibition')
        ->and($db->table('subscriptions')->value('user_type'))->toBe('exhibition')
        ->and($db->table('projects')->value('user_type'))->toBe('exhibition')
        ->and($db->table('users')->where('id', 'mat-1')->value('selected_user_type'))->toBe('exhibition');
});

it('merges analytics page materials into showrooms suppliers', function () {
    expect(mergeRun())->toBe(0);

    $db = DB::connection(mergeLegacyConnection());

    expect((int) $db->table('analytics_counts')->where('stat_key', 'showrooms-suppliers')->value('total_count'))->toBe(12)
        ->and($db->table('analytics_counts')->where('stat_key', 'materials')->count())->toBe(0)
        ->and((int) $db->table('analytics_counts')->where('stat_key', 'engineers')->value('total_count'))->toBe(11);
});

it('deletes legacy plan rows and keeps migrated users limits', function () {
    expect(mergeRun())->toBe(0);

    $db = DB::connection(mergeLegacyConnection());

    // باقات material تُحذف (0.5.5) وباقات exhibition تفضل.
    expect($db->table('plans')->pluck('user_type')->all())->toBe(['exhibition'])
        // صف الـ template بتاع material يُحذف…
        ->and($db->table('user_permissions')->whereNull('user_id')->count())->toBe(0)
        // …وفيها صف المستخدم المنقول يتحول exhibition وحدوده تفضل كما هي.
        ->and($db->table('user_permissions')->where('user_id', 'mat-1')->value('user_type'))->toBe('exhibition')
        ->and((int) $db->table('user_permissions')->where('user_id', 'mat-1')->value('max_visits'))->toBe(500);
});

it('drops the materials table and the legacy view after migration', function () {
    expect(mergeViewExists())->toBeTrue()
        ->and(mergeRun())->toBe(0)
        ->and(Schema::connection(mergeLegacyConnection())->hasTable('materials'))->toBeFalse()
        ->and(mergeViewExists())->toBeFalse()
        // الملفات المنقولة فضلت موجودة في المعارض.
        ->and(DB::connection(mergeLegacyConnection())->table('exhibitions')->count())->toBe(1);
});

it('is idempotent on a second run', function () {
    mergeRun();

    $db = DB::connection(mergeLegacyConnection());
    $snapshot = [
        'exhibitions' => $db->table('exhibitions')->count(),
        'services' => $db->table('services')->count(),
        'plans' => $db->table('plans')->count(),
        'permissions' => $db->table('user_permissions')->count(),
        'analytics' => $db->table('analytics_counts')->orderBy('stat_key')->pluck('total_count', 'stat_key')->all(),
    ];

    expect(mergeRun())->toBe(0);

    expect($db->table('exhibitions')->count())->toBe($snapshot['exhibitions'])
        ->and($db->table('services')->count())->toBe($snapshot['services'])
        ->and($db->table('plans')->count())->toBe($snapshot['plans'])
        ->and($db->table('user_permissions')->count())->toBe($snapshot['permissions'])
        ->and($db->table('analytics_counts')->orderBy('stat_key')->pluck('total_count', 'stat_key')->all())
        ->toBe($snapshot['analytics']);
});

it('is a safe no-op on the fresh new schema', function () {
    // من غير --database = الاتصال الحالي (schema الجديد) — ما فيش material أصلاً.
    expect(Artisan::call('t3meer:merge-materials'))->toBe(0)
        ->and(Artisan::output())->toContain('مفيش أي صف قديم');
});
