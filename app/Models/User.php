<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccountType;
use App\Enums\PlanType;
use App\Enums\Status;
use App\Notifications\PasswordResetLinkNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * المستخدم — SPEC 1.2.1.
 *
 * قواعد البناء (SPEC 0.4 + 7.1):
 * - id = CHAR(36) UUID v4 يولّده السيرفر (ممنوع uuid_generate_v4 في القاعدة).
 * - user_id/owner_id في الـ body بيتجاهلوا — الدور من auth()->id().
 * - user_type = نوع الحساب في الدليل؛ دور الأدمن كمان في user_roles (بيانات البروداكشن).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'name',
        'phone',
        'user_type',
        'slug',
        'current_step',
        'status',
        'selected_plan',
        'selected_billing_cycle',
        'selected_user_type',
        'profile_image',
        'rejection_reason',
    ];

    /** id = CHAR(36) uuid — ممنوع auto-increment (SPEC 7.1). */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'current_step' => 'integer',

            // قرارات التوحيد (SPEC 1.4) مفروضة على مستوى الكود:
            // rejected بدل reject (قرار #4)، professional بدل pro (قرار #1).
            'user_type' => AccountType::class,
            'status' => Status::class,
            'selected_plan' => PlanType::class,
        ];
    }

    /**
     * نوع الحساب كـ enum — استخدمه بدل قراءة الحقل مباشرة.
     * (الحقل المُلموس يرجع AccountType بسبب casts، والـ accessor بيحتمل الاتنين).
     */
    public function userType(): AccountType
    {
        $value = $this->getAttribute('user_type');

        return $value instanceof AccountType ? $value : AccountType::from((string) $value);
    }

    /**
     * قيمة نوع الحساب نصاً — لـ role middleware وOnboarding::stepRoute().
     */
    public function userTypeValue(): string
    {
        return $this->userType()->value;
    }

    /**
     * مولّد UUID v4 على السيرفر لكل مستخدم جديد (SPEC 7.1).
     * لا يُستدعى عند الترحيل — الاستيراد بيكتب الـ ids الأصلية كما هي.
     */
    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->getAttribute('id') === null || $user->getAttribute('id') === '') {
                $user->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    /** الأدوار الإضافية (بيانات البروداكشن: admin مسجّل هنا). */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /** كل الأدوار المدمجة: user_type + جدول user_roles. */
    public function allRoles(): array
    {
        $fromTable = $this->roles()->pluck('role')->all();

        return array_values(array_unique(array_filter(array_merge(
            [$this->userTypeValue()],
            $fromTable
        ))));
    }

    public function isAdmin(): bool
    {
        return in_array('admin', $this->allRoles(), true);
    }

    public function engineerProfile(): HasOne
    {
        return $this->hasOne(Engineer::class, 'user_id');
    }

    public function officeProfile(): HasOne
    {
        return $this->hasOne(Office::class, 'user_id');
    }

    public function exhibitionProfile(): HasOne
    {
        return $this->hasOne(Exhibition::class, 'user_id');
    }

    /** صلاحية الاشتراك الحالية (قرار #6: الحدود في user_permissions). */
    public function permissions(): HasOne
    {
        return $this->hasOne(UserPermission::class, 'user_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'user_id');
    }

    /** خدمات/تخصصات المالك (SPEC 1.2.6) — عدة صفوف. */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'user_id');
    }

    /** معرض الأعمال (SPEC 1.2.7). */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /** المنتجات (SPEC 1.2.8) — للمعرض فقط. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    /** المفضلة (SPEC 1.2.15). */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }

    /** التقييمات التي كتبها هذا المستخدم (SPEC 1.2.16). */
    public function writtenRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'user_id');
    }

    /** حملات التسويق (SPEC 1.2.19). */
    public function marketing(): HasOne
    {
        return $this->hasOne(Marketing::class, 'user_id');
    }

    /** بيانات التوثيق (SPEC 1.2.9). */
    public function validation(): HasOne
    {
        return $this->hasOne(UserValidation::class, 'user_id');
    }

    /** هل وصل المستخدم لأقصى خطوة في Onboarding (قرار #12 = المسار الحالي 2.A). */
    public function hasCompletedOnboarding(): bool
    {
        return (int) $this->getAttribute('current_step') >= 4;
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور (SPEC 2 — يوم 4).
     *
     * ⛔ بديل الافتراضي اللي بيبني الرابط من route `password.reset`
     * (مش موجودة — الفرونت بيقرأ `?token=&email=` من `/reset-password`).
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetLinkNotification($token));
    }
}
