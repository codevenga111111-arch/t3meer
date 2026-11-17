<?php

/**
 * اختبارات واجهات auth الكاملة — يوم 4 (المهام 11-13).
 *
 * المراجع: T3MER_CLICKUP_PLAN.md (اليوم 4) + SPEC 2 (register/login/forgot/
 * reset/verify) + 1.5.7/1.9.x (الأمان) + 2.V.6 (حقول /auth/me) +
 * 3.2.4 (حذف الحساب) + 3.4.5 (تغيير كلمة المرور).
 *
 * بيغطي سيناريوهات اليوم المطلوبة: رسائل عربية، 401/403/409/422/429،
 * ومنع user enumeration (نفس الرد للمسجّل وغير المسجّل).
 *
 * بيئة التشغيل: SQLite in-memory (phpunit.xml) = بيئة CI.
 */

use App\Models\User;
use App\Models\UserValidation;
use App\Notifications\PasswordResetLinkNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

// ---------------------------------------------------------------------------
// مساعدون
// ---------------------------------------------------------------------------

/** حbody التسجيل الافتراضي (SPEC 2 — RegisterForm). */
function authApiRegisterPayload(array $overrides = []): array
{
    return array_merge([
        'account_type' => 'engineer',
        'name' => 'أحمد محمد',
        'email' => 'eng.ahmed@example.com',
        'phone' => '01012345678',
        'password' => 'secret1',
        'password_confirmation' => 'secret1',
    ], $overrides);
}

/** حساب جاهز بكلمة مرور `password` (حالة factory = مفعّل). */
function authApiUser(array $overrides = []): User
{
    return User::factory()->create($overrides);
}

/** توكن صالح لاستخدامه في طلب Bearer. */
function authApiToken(User $user): string
{
    return $user->createToken('auth')->plainTextToken;
}

/** رابط تفعيل موقّع لحساب. */
function authApiVerifyUrl(User $user): string
{
    return VerifyEmailNotification::url($user);
}

/**
 * إعادة إنشاء guards بين طلبات نفس الاختبار.
 *
 * `RequestGuard` بيخزّن المستخدم بعد أول طلب و`AuthManager` بيخزّن الـ
 * guard — فبدون إعادة الإنشاء أي طلب تاني هيرجّع نفس الكائن القديم حتى
 * بعد إلغاء التوكن أو حذف الحساب. في الإنتاج كل طلب = boot جديد بلا كاش،
 * فالإعادة دي بتحاكي الحقيقة قبل فحوص ما بعد إلغاء الجلسة.
 */
function authApiFreshGuards(): void
{
    app('auth')->forgetGuards();
}

// ---------------------------------------------------------------------------
// المهمة 11: تسجيل + دخول + جلسة المستخدم
// ---------------------------------------------------------------------------

it('registers a new account with server-side defaults and a token', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/register', authApiRegisterPayload());

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'email', 'user_type', 'current_step', 'slug', 'next_route'],
                'token',
                'requires_email_verification',
            ],
            'message',
        ])
        ->assertJsonPath('data.requires_email_verification', true)
        ->assertJsonPath('data.user.email', 'eng.ahmed@example.com')
        ->assertJsonPath('data.user.user_type', 'engineer')
        ->assertJsonPath('data.user.current_step', 1)
        ->assertJsonPath('data.user.status', 'pending')
        ->assertJsonPath('data.user.selected_plan', 'free');

    $user = User::query()->where('email', 'eng.ahmed@example.com')->firstOrFail();

    // server-side فقط (1.9.3): خطوة/حالة/باقة مايجوش من الـ body.
    expect($user->slug)->not->toBeEmpty()
        ->and((int) $user->current_step)->toBe(1)
        ->and($user->status->value)->toBe('pending')
        ->and($user->selected_plan->value)->toBe('free')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmailNotification::class);

    // التوكن المُعاد فعّال فوراً.
    $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')->assertOk();
});

it('rejects a duplicate email with 409 account_exists', function () {
    authApiUser(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', authApiRegisterPayload([
        'email' => 'TAKEN@example.com', // التطبيع lowercase قبل الفحص.
        'phone' => '01099999999',
    ]));

    $response->assertStatus(409)
        ->assertJsonPath('code', 'account_exists')
        ->assertJsonPath('message', 'هذا الحساب مسجل لدينا بالفعل، يرجى تسجيل الدخول بدلاً من ذلك.');

    expect(User::query()->count())->toBe(1);
});

it('rejects an invalid phone with an arabic 422', function () {
    $response = $this->postJson('/api/v1/auth/register', authApiRegisterPayload(['phone' => '12345']));

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['phone']])
        ->assertJsonPath('errors.phone.0', 'رقم الهاتف غير صالح — مثال: 01012345678');
});

it('rejects a phone that belongs to another account with 422', function () {
    authApiUser(['phone' => '01012345678']);

    $response = $this->postJson('/api/v1/auth/register', authApiRegisterPayload([
        'email' => 'other@example.com',
        'phone' => '01012345678',
    ]));

    $response->assertStatus(422)
        ->assertJsonPath('errors.phone.0', 'رقم الهاتف هذا مسجل لمستخدم آخر بالفعل.');
});

it('normalizes arabic phone digits before validation', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', authApiRegisterPayload([
        'email' => 'arabic@example.com',
        'phone' => '٠١٠١٢٣٤٥٦٧٨',
    ]))->assertCreated();

    expect(User::query()->where('email', 'arabic@example.com')->value('phone'))->toBe('01012345678');
});

it('logs in with valid credentials', function () {
    $user = authApiUser();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user']])
        ->assertJsonPath('data.user.email', $user->email);
});

it('rejects invalid credentials with 401 invalid_credentials and no enumeration', function () {
    $user = authApiUser();

    $unknown = $this->postJson('/api/v1/auth/login', [
        'email' => 'ghost@example.com',
        'password' => 'secret1',
    ]);
    $wrongPassword = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-pass',
    ]);

    $unknown->assertStatus(401)->assertJsonPath('code', 'invalid_credentials');
    $wrongPassword->assertStatus(401)->assertJsonPath('code', 'invalid_credentials');

    // نفس الرسالة للحالتين — البريد غير المسجّل مايتعرفش من الرد (1.9.6).
    expect($unknown->json('message'))->toBe($wrongPassword->json('message'))
        ->and($unknown->json('message'))->toContain('غير صحيحة');
});

it('blocks login before email confirmation with 403 email_not_confirmed', function () {
    $user = User::factory()->unverified()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertStatus(403)->assertJsonPath('code', 'email_not_confirmed');
});

it('returns the current user with onboarding and validation fields', function () {
    $user = User::factory()->create([
        'user_type' => 'engineer',
        'current_step' => 1,
        'status' => 'pending',
        'selected_plan' => 'free',
        'email_verified_at' => null,
    ]);

    $response = $this->withToken(authApiToken($user))->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('data.user_type', 'engineer')
        ->assertJsonPath('data.current_step', 1)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.selected_plan', 'free')
        ->assertJsonPath('data.slug', $user->slug)
        ->assertJsonPath('data.email_verified_at', null)
        ->assertJsonPath('data.next_route', '/validation/engineer')
        ->assertJsonPath('data.validation_required', true)
        ->assertJsonPath('data.validation_status', null)
        ->assertJsonPath('data.is_verified', false);

    // بعد إرسال التوثيق: مفيش توثيق "مطلوب"، والحالة تظهر (2.V.6).
    UserValidation::create([
        'user_id' => $user->id,
        'name' => $user->name,
        'account_type' => 'engineer',
        'status' => 'pending',
    ]);

    authApiFreshGuards();

    $this->withToken(authApiToken($user))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.validation_required', false)
        ->assertJsonPath('data.validation_status', 'pending')
        ->assertJsonPath('data.is_verified', false);

    // توثيق معتمد → is_verified true (1.7.6 — من user_validation لا عمود).
    UserValidation::query()->where('user_id', $user->id)->update(['status' => 'active']);

    authApiFreshGuards();

    $this->withToken(authApiToken($user))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.is_verified', true)
        ->assertJsonPath('data.validation_status', 'active');
});

it('requires a bearer token for /auth/me', function () {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('logs out by revoking the current token', function () {
    $user = authApiUser();
    $token = authApiToken($user);

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(204);

    // التوكن الملغى مايعمّلش أي طلب بعده.
    authApiFreshGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
});

// ---------------------------------------------------------------------------
// المهمة 12: تفعيل البريد + إعادة الإرسال + تغيير البريد غير المؤكد
// ---------------------------------------------------------------------------

it('verifies the email through a signed link and redirects by account type', function () {
    $cases = [
        ['engineer', '/validation/engineer'],
        ['office', '/validation/office'],
        ['exhibition', '/pricing/exhibition'],
        ['customer', '/customer/setup'],
    ];

    foreach ($cases as [$type, $route]) {
        $user = User::factory()->unverified()->create([
            'user_type' => $type,
            'current_step' => 1,
        ]);

        $this->get(authApiVerifyUrl($user))
            ->assertStatus(302)
            ->assertRedirect(config('app.url').$route);

        $user->refresh();

        expect($user->email_verified_at)->not->toBeNull()
            // ⛔ التفعيل مايرفعش الخطوة (2.V.2/2.V.7#2) — المهندس/المكتب
            // يفضلوا step 1 عشان next_route = /validation/{type}.
            ->and((int) $user->current_step)->toBe(1);
    }
});

it('rejects a tampered verification link', function () {
    $user = User::factory()->unverified()->create();

    $url = authApiVerifyUrl($user);
    $tampered = str_replace(sha1($user->email), str_repeat('f', 40), $url);

    $this->get($tampered)->assertStatus(403);

    expect($user->refresh()->email_verified_at)->toBeNull();
});

it('resends the verification link with a 60 second throttle', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->postJson('/api/v1/auth/email/resend', ['email' => $user->email, 'account_type' => 'engineer'])
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmailNotification::class);

    // الفرونت countdown 60 ثانية → ثانية واحدة فقط (throttle:1,1) → 429.
    $this->postJson('/api/v1/auth/email/resend', ['email' => $user->email])
        ->assertStatus(429);

    Notification::assertSentTimes(VerifyEmailNotification::class, 1);
});

it('changes an unconfirmed email and revokes the old sessions', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create(['email' => 'old@example.com', 'slug' => 'oldexamplecom']);
    $token = authApiToken($user);

    // slug = local-part من الإيميل (Slug::fromEmail) — "new".
    $this->patchJson('/api/v1/auth/email', ['new_email' => 'new@example.com'], [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('data.success', true)
        ->assertJsonPath('data.new_email', 'new@example.com')
        ->assertJsonPath('data.new_slug', 'new')
        ->assertJsonPath('data.email_sent', true)
        ->assertJsonStructure(['data' => ['success', 'new_email', 'new_slug', 'email_sent', 'message']]);

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->slug)->toBe('new')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmailNotification::class);

    // الجلسات القديمة اتلغت لأن الجلسة مرتبطة بالبريد القديم (1.9.5).
    authApiFreshGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('rejects changing a confirmed email', function () {
    $user = authApiUser();

    $this->patchJson('/api/v1/auth/email', ['new_email' => 'other@example.com'], [
        'Authorization' => 'Bearer '.authApiToken($user),
    ])->assertStatus(422)
        ->assertJsonPath('errors.new_email.0', 'تم تأكيد بريدك الإلكتروني مسبقاً، سجّل الدخول لتغييره');
});

it('rejects taking an email that belongs to another account', function () {
    authApiUser(['email' => 'busy@example.com']);
    $user = User::factory()->unverified()->create();

    $this->patchJson('/api/v1/auth/email', ['new_email' => 'busy@example.com'], [
        'Authorization' => 'Bearer '.authApiToken($user),
    ])->assertStatus(422)
        ->assertJsonPath('errors.new_email.0', 'هذا البريد الإلكتروني مسجل بالفعل');
});

it('requires a bearer token to change the email', function () {
    $this->patchJson('/api/v1/auth/email', ['new_email' => 'x@example.com'])->assertStatus(401);
});

// ---------------------------------------------------------------------------
// المهمة 13: نسيت/تغيير كلمة المرور + حذف الحساب
// ---------------------------------------------------------------------------

it('answers 200 on forgot password whether or not the account exists', function () {
    Notification::fake();

    $user = authApiUser();

    $known = $this->postJson('/api/v1/auth/password/forgot', ['email' => $user->email])->assertOk();
    $unknown = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'ghost@example.com'])->assertOk();

    // نفس الرد والرسالة للحالتين — منع user enumeration.
    expect($known->json('message'))->toBe($unknown->json('message'));

    Notification::assertSentTo($user, PasswordResetLinkNotification::class, function ($notification, $notifiable) use ($user) {
        $url = $notification->url($user);

        // الشكل المطلوب: {APP_URL}/reset-password?token={token}&email={email}
        return str_starts_with($url, config('app.url').'/reset-password?token=')
            && str_contains($url, '&email='.urlencode($user->email));
    });
});

it('throttles forgot password after three requests', function () {
    $user = authApiUser();

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/password/forgot', ['email' => $user->email])->assertOk();
    }

    // المحاولة الرابعة = throttle:3,1 (429) — أما أي بريد فـ 200 دائماً.
    $this->postJson('/api/v1/auth/password/forgot', ['email' => $user->email])->assertStatus(429);
});

it('resets the password with a valid token', function () {
    $user = authApiUser();
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ])->assertOk();

    // الدخول بالجديدة نجح وبالقديمة رُفض.
    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'newpass1',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertStatus(401);
});

it('rejects an invalid or expired reset token with 422', function () {
    $user = authApiUser();

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ])->assertStatus(422)
        ->assertJsonPath('errors.token.0', 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية.');

    // توكن منتهٍ (expire = 60 دقيقة في config/auth.php).
    $token = Password::broker()->createToken($user);
    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subHours(2)]);

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ])->assertStatus(422)
        ->assertJsonPath('errors.token.0', 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية.');
});

it('requires the current password when changing it', function () {
    $user = authApiUser();
    $headers = ['Authorization' => 'Bearer '.authApiToken($user)];

    // بدون current_password → 422 (الكود القديم كان مايطلبهاش — 3.4.5).
    $this->putJson('/api/v1/me/password', [
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ], $headers)->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['current_password']]);

    // current_password خاطئة → 422 برسالة عربية.
    $this->putJson('/api/v1/me/password', [
        'current_password' => 'wrong-pass',
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ], $headers)->assertStatus(422)
        ->assertJsonPath('errors.current_password.0', 'كلمة المرور الحالية غير صحيحة.');

    // صحيحة → 204.
    $this->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'newpass1',
        'password_confirmation' => 'newpass1',
    ], $headers)->assertStatus(204);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'newpass1',
    ])->assertOk();
});

it('deletes the account only after explicit confirmation', function () {
    $user = authApiUser();
    $token = authApiToken($user);
    $headers = ['Authorization' => 'Bearer '.$token];

    // بدون confirm أو بقيمة غير مقبولة → 422 (confirm: accepted).
    $this->deleteJson('/api/v1/me', [], $headers)->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['confirm']]);

    $this->deleteJson('/api/v1/me', ['confirm' => 'no'], $headers)->assertStatus(422);

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();

    // مؤكد → 204 والحساب اختفى وكل جلساته اتلغت (3.2.4).
    $this->deleteJson('/api/v1/me', ['confirm' => true], $headers)->assertStatus(204);

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();

    authApiFreshGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
});
