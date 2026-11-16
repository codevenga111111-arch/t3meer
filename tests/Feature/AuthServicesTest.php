<?php

/**
 * اختبارات استبدال الـ RPCs والـ trigger بمنطق Laravel — المصادقة والحساب
 * (SPEC 1.4 + 1.9.5 + 1.9.6 + 1.9.16 + 1.10.3 + مهمة ClickUp #9).
 *
 * بيغطي:
 * - `check_user_exists`          → UserLookupService (بدون تسريب user_id)
 * - `handle_new_user` (trigger)  → UserProvisioner (whitelists + بلا صمت)
 * - `update_unconfirmed_user_email` → EmailChangeService (مصادقة فقط)
 * - `delete_user_account`        → AccountDeletionService (حذف صريح بلا cascade)
 *
 * بيئة التشغيل: SQLite in-memory (phpunit.xml) = بيئة CI.
 */

use App\Models\Customer;
use App\Models\CustomerRequestAssignment;
use App\Models\Engineer;
use App\Models\Favorite;
use App\Models\InteractionLog;
use App\Models\Marketing;
use App\Models\Office;
use App\Models\Product;
use App\Models\Project;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use App\Models\UserValidation;
use App\Services\AccountDeletionService;
use App\Services\EmailChangeService;
use App\Services\UserLookupService;
use App\Services\UserProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ---------------------------------------------------------------------------
// check_user_exists → UserLookupService (1.9.6)
// ---------------------------------------------------------------------------

it('normalizes and looks up an account by email', function () {
    // البريد بيتسجّل دايماً lowercase (register + الاستيراد) — والخدمة بتطبيع المُدخل.
    $user = User::factory()->create(['email' => 'someone@example.com']);
    $lookup = new UserLookupService;

    expect($lookup->normalize('  Someone@EXAMPLE.com '))->toBe('someone@example.com')
        ->and($lookup->exists(' SOMEONE@example.COM '))->toBeTrue()
        ->and($lookup->find('someone@example.com')?->id)->toBe($user->id)
        ->and($lookup->resolveId('someone@example.com'))->toBe($user->id);
});

it('never leaks anything for unknown or empty emails', function () {
    $lookup = new UserLookupService;

    expect($lookup->exists('nobody@example.com'))->toBeFalse()
        ->and($lookup->exists(''))->toBeFalse()
        ->and($lookup->find(''))->toBeNull()
        ->and($lookup->resolveId('nobody@example.com'))->toBeNull();
});

// ---------------------------------------------------------------------------
// handle_new_user (trigger) → UserProvisioner (1.9.16)
// ---------------------------------------------------------------------------

it('provisions a new account like the signup trigger', function () {
    $user = (new UserProvisioner)->provision([
        'email' => '  Ahmed.Sayed@Example.COM ',
        'user_type' => 'engineer',
        'name' => 'أحمد سيد',
        'phone' => '01012345678',
        'password' => 'secret-123',
    ]);

    expect($user->email)->toBe('ahmed.sayed@example.com')
        ->and($user->slug)->toBe('ahmed-sayed')          // من الإيميل (1.4)
        ->and($user->user_type->value)->toBe('engineer')
        ->and($user->name)->toBe('أحمد سيد')
        ->and($user->phone)->toBe('01012345678')
        ->and($user->password)->not->toBe('secret-123')  // bcrypt
        ->and(User::query()->where('email', 'ahmed.sayed@example.com')->exists())->toBeTrue();
});

it('adds numeric suffixes until the slug is unique', function () {
    User::factory()->create(['slug' => 'omar']);
    User::factory()->create(['slug' => 'omar-1']);

    $user = (new UserProvisioner)->provision([
        'email' => 'omar@example.com',
        'user_type' => 'exhibition',
    ]);

    expect($user->slug)->toBe('omar-2');
});

it('rejects unknown account types instead of silently falling back to engineer', function () {
    expect(fn () => (new UserProvisioner)->provision([
        'email' => 'x@example.com',
        'user_type' => 'admin', // ممنوع من التسجيل العام (1.9.3)
    ]))->toThrow(InvalidArgumentException::class, 'نوع الحساب غير صالح');

    expect(fn () => (new UserProvisioner)->provision([
        'email' => 'x@example.com',
        'user_type' => 'material', // مندوب من القائمة النهائية (0.5)
    ]))->toThrow(InvalidArgumentException::class, 'نوع الحساب غير صالح');
});

it('rejects invalid or already taken emails', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    expect(fn () => (new UserProvisioner)->provision([
        'email' => 'not-an-email',
        'user_type' => 'customer',
    ]))->toThrow(InvalidArgumentException::class, 'البريد الإلكتروني غير صالح');

    expect(fn () => (new UserProvisioner)->provision([
        'email' => 'TAKEN@example.com',
        'user_type' => 'customer',
    ]))->toThrow(InvalidArgumentException::class, 'هذا البريد الإلكتروني مسجل بالفعل');
});

it('normalizes arabic phone digits and rejects invalid numbers', function () {
    $user = (new UserProvisioner)->provision([
        'email' => 'phone-case@example.com',
        'user_type' => 'office',
        'phone' => '٠١١٢٣٤٥٦٧٨٩',
    ]);

    expect($user->phone)->toBe('01123456789');

    expect(fn () => (new UserProvisioner)->provision([
        'email' => 'bad-phone@example.com',
        'user_type' => 'office',
        'phone' => '12345',
    ]))->toThrow(InvalidArgumentException::class, 'رقم الهاتف غير صالح');
});

it('keeps status, step and plan server side (1.9.3)', function () {
    $user = (new UserProvisioner)->provision([
        'email' => 'sneaky@example.com',
        'user_type' => 'engineer',
        // محاولة تزوير الحالة/الخطوة/الباقة من الـ body — كلها بتتجاهل.
        'status' => 'active',
        'current_step' => 4,
        'selected_plan' => 'premium',
    ]);

    expect($user->status->value)->toBe('pending')
        ->and($user->current_step)->toBe(1)
        ->and($user->selected_plan->value)->toBe('free');
});

// ---------------------------------------------------------------------------
// update_unconfirmed_user_email → EmailChangeService (1.9.5)
// ---------------------------------------------------------------------------

it('changes an unconfirmed email and regenerates the slug', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'old@example.com',
        'slug' => 'oldslug',
    ]);

    $result = (new EmailChangeService)->change($user, '  New.Address@Example.COM ');

    $user->refresh();

    expect($result)->toBe([
        'success' => true,
        'new_email' => 'new.address@example.com',
        'new_slug' => 'new-address',
    ])
        ->and($user->email)->toBe('new.address@example.com')
        ->and($user->slug)->toBe('new-address')
        ->and($user->getAttribute('email_verified_at'))->toBeNull();
});

it('refuses to change a confirmed email', function () {
    $user = User::factory()->create(['email' => 'confirmed@example.com']); // verified by factory

    expect(fn () => (new EmailChangeService)->change($user, 'other@example.com'))
        ->toThrow(InvalidArgumentException::class, 'تم تأكيد بريدك الإلكتروني مسبقاً');
});

it('refuses the same email and an email owned by another account', function () {
    $user = User::factory()->unverified()->create(['email' => 'same@example.com']);
    User::factory()->create(['email' => 'busy@example.com']);

    expect(fn () => (new EmailChangeService)->change($user, 'SAME@example.com'))
        ->toThrow(InvalidArgumentException::class, 'يجب أن يختلف');

    expect(fn () => (new EmailChangeService)->change($user, 'busy@example.com'))
        ->toThrow(InvalidArgumentException::class, 'مسجل بالفعل');
});

it('sanitizes a requested slug and keeps it unique', function () {
    $taken = User::factory()->create(['slug' => 'my-slug']);
    $user = User::factory()->unverified()->create(['slug' => 'draft-slug']);

    $result = (new EmailChangeService)->change($user, 'fresh@example.com', '  My Slug!!  ');

    expect($result['new_slug'])->toBe('my-slug-1')
        ->and(User::query()->where('slug', 'my-slug')->count())->toBe(1)
        ->and($taken->slug)->toBe('my-slug');
});

it('revokes every session when the email changes', function () {
    $user = User::factory()->unverified()->create();
    $user->createToken('session-1');
    $user->createToken('session-2');

    expect($user->tokens()->count())->toBe(2);

    (new EmailChangeService)->change($user, 'renamed@example.com');

    expect($user->tokens()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// delete_user_account → AccountDeletionService (قرار 1.10.3: بلا cascade)
// ---------------------------------------------------------------------------

it('deletes the account with everything that belongs to it', function () {
    $user = User::factory()->create(['slug' => 'leaving-user']);
    $bystander = User::factory()->create(['slug' => 'staying-user']);

    // المحتوى والملف.
    Office::create(['user_id' => $user->id, 'name' => 'مكتبه', 'slug' => $user->slug]);
    Engineer::create(['user_id' => $user->id, 'name' => 'مهندسه', 'slug' => $user->slug]);
    Service::create(['user_id' => $user->id, 'name' => 'تصميم داخلي', 'slug' => $user->slug]);
    Project::create(['user_id' => $user->id, 'name' => 'مشروعه', 'slug' => $user->slug]);
    Product::create(['user_id' => $user->id, 'name' => 'منتجه', 'slug' => $user->slug]);

    // التسويق والاشتراك والصلاحيات والتوثيق والسجلات.
    Marketing::create(['user_id' => $user->id, 'slug' => $user->slug]);
    $subscription = Subscription::create([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'plan_type' => 'premium',
    ]);
    SubscriptionPayment::create([
        'id' => (string) Str::uuid(),
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'amount' => 100,
        'plan_type' => 'premium',
    ]);
    UserPermission::create(['user_id' => $user->id, 'user_type' => 'office', 'plan_type' => 'premium']);
    UserValidation::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'name' => 'مكتبه']);
    UserRole::create(['user_id' => $user->id, 'role' => 'admin']);
    InteractionLog::create(['user_id' => $user->id, 'activity_text' => 'حدث ما']);

    // المفضلة والتقييمات: كتبها + عن ملفه (بيتمسحوا) + نسخ الباقين عن حد تاني (تفضل).
    Favorite::create(['user_id' => $user->id, 'name' => 'بكتبها', 'type' => 'office', 'slug' => 'someone-else']);
    Favorite::create(['user_id' => $bystander->id, 'name' => 'عنه', 'type' => 'office', 'slug' => $user->slug]);
    Favorite::create(['user_id' => $bystander->id, 'name' => 'عن غيره', 'type' => 'office', 'slug' => 'someone-else']);
    Rating::create(['slug' => 'someone-else', 'target_type' => 'office', 'user_id' => $user->id, 'name' => 'بكتبها', 'rating' => 5]);
    Rating::create(['slug' => $user->slug, 'target_type' => 'office', 'user_id' => $bystander->id, 'name' => 'عنه', 'rating' => 4]);
    Rating::create(['slug' => 'someone-else', 'target_type' => 'office', 'user_id' => $bystander->id, 'name' => 'عن غيره', 'rating' => 3]);

    // طلب عميل مسند له + طلب يملكه + طلب لبائع تاني (يفضل مع تعيينه).
    $assigned = Customer::create(['id' => (string) Str::uuid(), 'customer_type' => 'need_service', 'assigned_provider_id' => $user->id]);
    $owned = Customer::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'customer_type' => 'guest']);
    $bystanderCustomer = Customer::create(['id' => (string) Str::uuid(), 'user_id' => $bystander->id, 'customer_type' => 'guest']);
    CustomerRequestAssignment::create(['customer_id' => $assigned->id, 'provider_id' => $user->id, 'action' => 'assigned']);
    CustomerRequestAssignment::create(['customer_id' => $owned->id, 'provider_id' => $bystander->id, 'action' => 'assigned']);
    CustomerRequestAssignment::create(['customer_id' => $bystanderCustomer->id, 'provider_id' => $bystander->id, 'action' => 'assigned']);

    $user->createToken('session');

    // بيانات الباقية (عدّادات مجهولة + مرجعية + بيانات الباقين) لازم تفضل.
    DB::table('stats')->insert(['slug' => $user->slug, 'metric_type' => 'visit', 'date' => now()->toDateString(), 'count' => 5]);
    DB::table('analytics_counts')->insert(['stat_type' => 'page', 'stat_key' => 'home', 'total_count' => 10]);

    (new AccountDeletionService)->delete($user);

    // الحساب وكل ما يخصه راح.
    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Office::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Engineer::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Service::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Project::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Product::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Marketing::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Subscription::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(SubscriptionPayment::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(UserPermission::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(UserValidation::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(UserRole::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(InteractionLog::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Favorite::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Favorite::query()->where('slug', $user->slug)->count())->toBe(0)
        ->and(Rating::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Rating::query()->where('slug', $user->slug)->count())->toBe(0)
        ->and(DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count())->toBe(0)
        ->and(Customer::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(CustomerRequestAssignment::query()->where('provider_id', $user->id)->count())->toBe(0);

    // الطلب المسند له اتعرّض له بس ما اتحذفش (صاحبه لسه موجود).
    $assigned->refresh();
    expect($assigned->assigned_provider_id)->toBeNull();

    // ما يلمسش حد تاني + العدّادات المجهولة فاضلة.
    expect(User::query()->whereKey($bystander->id)->exists())->toBeTrue()
        ->and(Favorite::query()->where('user_id', $bystander->id)->count())->toBe(1)
        ->and(Rating::query()->where('user_id', $bystander->id)->count())->toBe(1)
        ->and(CustomerRequestAssignment::query()->where('provider_id', $bystander->id)->count())->toBe(1)
        ->and(DB::table('stats')->where('slug', $user->slug)->count())->toBe(1)
        ->and(DB::table('analytics_counts')->where('stat_key', 'home')->value('total_count'))->toBe(10);
});
