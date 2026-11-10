<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * طلب خدمة عميل (SPEC 1.2.14 + قرار 7.1#7).
 *
 * - customer_type: guest | need_service.
 * - status: قيم مغلقة موحّدة (قرار #4) — منها `deleted` لـ soft delete.
 * - ⛔ notes لم يعد سجلاً بـ regex (`[رفض سابق: …]`) — التاريخ في
 *   customer_request_assignments (قرار #7).
 * - whats_count/calls_count تزيد بـ increment() ذرّي (SPEC 3.10).
 */
class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'id', 'user_id', 'full_name', 'phone', 'email', 'customer_type',
        'governorate', 'service', 'notes', 'assigned_provider_id',
        'assigned_provider_name', 'assigned_at', 'status',
        'customer_response', 'customer_reason', 'whats_count', 'calls_count',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'whats_count' => 'integer',
            'calls_count' => 'integer',
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

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_provider_id');
    }

    /** سجل الترشيح/الرفض (قرار #7). */
    public function assignments()
    {
        return $this->hasMany(CustomerRequestAssignment::class, 'customer_id');
    }

    /** هل الطلب محذوف (soft delete)؟ */
    public function isDeleted(): bool
    {
        return $this->getAttribute('status') === 'deleted';
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            'pending', 'active', 'assigned', 'approved', 'in_progress',
            'completed', 'cancelled', 'refused', 'deleted',
        ];
    }
}
