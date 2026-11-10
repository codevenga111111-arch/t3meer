<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * الاشتراك (SPEC 1.2.6) — قرار #5: صف واحد لكل مستخدم،
 * السعر والتواريخ تُحسب server-side (ممنوع جلبها من body).
 */
class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'id', 'user_id', 'plan_type', 'plan_slug', 'billing_cycle', 'user_type',
        'price', 'confirmation', 'status', 'starts_at', 'expires_at', 'is_renewal',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'price' => 'float:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_renewal' => 'boolean',
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

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->getAttribute('status') === 'active'
            && ($this->getAttribute('expires_at') === null
                || $this->getAttribute('expires_at')->isFuture());
    }
}
