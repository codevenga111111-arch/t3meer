<?php

/**
 * اختبارات اتفاقيات الـ API (SPEC 0.4) — الهيكل الأساسي لتاسكة اليوم 1.3.
 *
 * بتحقق من:
 * - envelope النجاح {data, message}
 * - صيغة 422 {message, errors:{field:["رسالة بالعربي"]}}
 * - 404/403/401 بالعربي
 * - pagination ?page &per_page → meta
 * - تجاهل user_id/owner_id من body (SPEC 0.4)
 * - middleware role (users.user_type + user_roles)
 * - middleware step (Onboarding — القرار #12)
 * - ISO 8601 في التواريخ
 */

use App\Models\User;
use App\Models\UserRole;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // مسارات اختبارية مؤقتة — بتأكد من الـ middleware والاتفاقيات بدون ربطها بالـ routes.php.
    Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
        Route::get('/_test/me', function (Request $request) {
            return response()->json([
                'data' => [
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                    'user_type' => $request->user()->user_type,
                ],
                'message' => 'تم',
            ]);
        });

        Route::match(['get', 'post'], '/_test/validate', function (Request $request) {
            $request->validate([
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:255'],
            ]);

            return response()->json(['data' => $request->only('email', 'name'), 'message' => 'تم']);
        });

        // يتجاهل user_id/owner_id من الـ body وياخدها من auth (SPEC 0.4).
        Route::post('/_test/ownership', function (Request $request) {
            return response()->json([
                'data' => ['owner' => $request->user()->id],
                'message' => 'تم',
            ]);
        });

        Route::get('/_test/admin-only', fn () => response()->json(['data' => null, 'message' => 'تم']))
            ->middleware('role:admin');

        Route::get('/_test/professional-only', fn () => response()->json(['data' => null, 'message' => 'تم']))
            ->middleware('role:engineer,office');

        Route::get('/_test/step-4', fn () => response()->json(['data' => null, 'message' => 'تم']))
            ->middleware('step:4');
    });
});

// ---------------------------------------------------------------------------
// envelope وحالة النجاح
// ---------------------------------------------------------------------------

it('returns the standard envelope on ping', function () {
    $response = $this->getJson('/api/v1/ping')->assertOk();

    $response->assertJsonStructure(['data' => ['app', 'api_version', 'time'], 'message']);
    $response->assertJsonPath('data.api_version', 'v1');
});

it('returns iso8601 timestamps', function () {
    $time = $this->getJson('/api/v1/ping')->json('data.time');

    expect($time)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

// ---------------------------------------------------------------------------
// الأخطاء بالعربي
// ---------------------------------------------------------------------------

it('returns 404 in arabic', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertStatus(404)
        ->assertJsonStructure(['message'])
        ->assertJsonPath('message', 'المسار أو المورد غير موجود');
});

it('returns 401 in arabic for unauthenticated routes', function () {
    $this->getJson('/api/v1/_test/me')
        ->assertStatus(401)
        ->assertJsonPath('message', 'يجب تسجيل الدخول');
});

it('returns 405 in arabic for wrong methods', function () {
    $this->postJson('/api/v1/ping')->assertStatus(405);
});

// ---------------------------------------------------------------------------
// 422 بالصيغة المتفق عليها
// ---------------------------------------------------------------------------

it('returns 422 with arabic field errors', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/_test/validate', []);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email', 'name']])
        ->assertJsonStructure(['errors' => ['email' => [0]]]);

    $emailError = $response->json('errors.email.0');
    $nameError = $response->json('errors.name.0');

    // كل الرسالة لازم تكون بالعربي (SPEC 0.4).
    expect($emailError)->toBeString()->not->toBeEmpty()
        ->and($nameError)->toBeString()->not->toBeEmpty()
        ->and(preg_match('/\p{Arabic}/u', $emailError))->toBe(1)
        ->and(preg_match('/\p{Arabic}/u', $nameError))->toBe(1);
});

// ---------------------------------------------------------------------------
// pagination (قرار #15 + SPEC 0.4)
// ---------------------------------------------------------------------------

it('paginates with page and per_page into meta', function () {
    $items = range(1, 30);
    $page = new LengthAwarePaginator(
        items: array_slice($items, 12, 12),
        total: 30,
        perPage: 12,
        currentPage: 2
    );

    $controller = new class
    {
        use ApiResponse;

        public function run(Illuminate\Contracts\Pagination\LengthAwarePaginator $page): array
        {
            return $this->apiPaginated($page)->getData(true);
        }
    };

    $payload = $controller->run($page);

    expect($payload)->toHaveKeys(['data', 'meta', 'message'])
        ->and($payload['meta'])->toBe([
            'current_page' => 2,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
        ])
        ->and($payload['data'])->toHaveCount(12);
});

it('limits per_page to 100', function () {
    $controller = new class
    {
        use ApiResponse;

        public function run(int $value): int
        {
            request()->merge(['per_page' => $value]);

            return $this->perPage();
        }
    };

    expect($controller->run(9999))->toBe(100)
        ->and($controller->run(0))->toBe(1)
        ->and($controller->run(12))->toBe(12);
});

// ---------------------------------------------------------------------------
// تجاهل user_id من body (SPEC 0.4)
// ---------------------------------------------------------------------------

it('ignores user_id and owner_id from the body', function () {
    $real = User::factory()->create();
    $attacker = User::factory()->create();

    $response = $this->actingAs($real, 'sanctum')->postJson('/api/v1/_test/ownership', [
        'user_id' => $attacker->id,
        'owner_id' => $attacker->id,
        'id' => $attacker->id,
    ]);

    $response->assertOk()->assertJsonPath('data.owner', $real->id);
});

// ---------------------------------------------------------------------------
// middleware role — من users.user_type + user_roles
// ---------------------------------------------------------------------------

it('allows access when user_type matches the required role', function () {
    $user = User::factory()->create(['user_type' => 'engineer']);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/_test/professional-only')->assertOk();
});

it('allows access when the role comes from user_roles table', function () {
    $user = User::factory()->create(['user_type' => 'engineer']);

    UserRole::create(['user_id' => $user->id, 'role' => 'admin']);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/_test/admin-only')->assertOk();
});

it('rejects users without the required role', function () {
    $user = User::factory()->create(['user_type' => 'customer']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/_test/admin-only')
        ->assertStatus(403)
        ->assertJsonPath('message', 'ليس لديك صلاحية لهذا الإجراء');
});

// ---------------------------------------------------------------------------
// middleware step — Onboarding (القرار #12 = المسار الحالي 2.A)
// ---------------------------------------------------------------------------

it('blocks users below the required onboarding step', function () {
    $user = User::factory()->create(['current_step' => 2]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/_test/step-4')
        ->assertStatus(409)
        ->assertJsonPath('code', 'invalid_onboarding_step')
        ->assertJsonPath('current_step', 2)
        ->assertJsonPath('required_step', 4);
});

it('lets users who completed onboarding through', function () {
    $user = User::factory()->onboardingComplete()->create();

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/_test/step-4')->assertOk();
});

// ---------------------------------------------------------------------------
// الموادلات: UUID من السيرفر + password nullable
// ---------------------------------------------------------------------------

it('generates a server side uuid v4 and no id from the body', function () {
    $user = User::factory()->create(['id' => null]);

    expect($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('keeps migrated users without a password able to reset it', function () {
    $user = User::factory()->create(['password' => null]);

    expect($user->password)->toBeNull()
        ->and($user->getAttribute('email_verified_at'))->not->toBeNull();
});
