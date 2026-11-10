<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

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
    use HasFactory, Notifiable;

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
        ];
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
            [(string) $this->getAttribute('user_type')],
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

    /** هل وصل المستخدم لأقصى خطوة في Onboarding (قرار #12 = المسار الحالي 2.A). */
    public function hasCompletedOnboarding(): bool
    {
        return (int) $this->getAttribute('current_step') >= 4;
    }
}
