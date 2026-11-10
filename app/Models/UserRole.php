<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * دور إضافي للمستخدم (جدول موجود في بيانات البروداكشن وغائب من SPEC 1.2).
 *
 * السبب: المهندس/المكتب يقدر يكون admin كمان — الدور بيُقرأ من هنا
 * بالإضافة إلى users.user_type (middleware role).
 */
class UserRole extends Model
{
    protected $table = 'user_roles';

    protected $fillable = ['id', 'user_id', 'role'];

    public $timestamps = true;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            if (! $role->getAttribute('id')) {
                $role->setAttribute('id', (string) Str::uuid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
