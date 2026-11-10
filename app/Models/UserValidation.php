<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * بيانات التوثيق (SPEC 1.2.9).
 *
 * - صف واحد لكل مستخدم (UNIQUE user_id) — updateOrCreate وليس upsert.
 * - ⛔ ممنوع upsert الـ placeholder اللي بيمسح المستندات الحقيقية
 *   (أرقام عشوائية + placehold.co) — SPEC 2.C#3 و1.8.2.
 * - الحالة: pending | active | rejected (بدون "reject") — قرار #4.
 *   الأدمن فقط يغيّرها (قرار #3).
 * - details: مهندس {graduation_year, specialization} / مكتب {tax_id}.
 */
class UserValidation extends Model
{
    protected $table = 'user_validation';

    protected $fillable = [
        'id', 'user_id', 'name', 'account_type', 'professional_id',
        'tax_number', 'documents', 'details', 'status', 'rejection_reason',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'details' => 'array',
            'status' => Status::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->getAttribute('id')) {
                $model->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** هل التوثيق مقبول فعلاً؟ */
    public function isApproved(): bool
    {
        return $this->getAttribute('status') === Status::Active;
    }
}
