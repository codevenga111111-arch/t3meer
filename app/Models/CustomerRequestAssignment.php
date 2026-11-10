<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل ترشيح/رفض طلب عميل (قرار 7.1#7 — مهمة ClickUp #6).
 *
 * بديل نصوص `[رفض سابق: <اسم> - السبب: <سبب>]` الملصقة في customers.notes
 * واللي كانت بتتحل بـ regex (office-requests.api.ts:109).
 *
 * - action: assigned | reassigned | refused | completed | cancelled.
 * - acted_by: الأدمن/الموظف الذي نفّذ الإجراء (audit).
 * - provider_name: اسم المزوّد وقت التنفيذ (snapshot — الاسم ممكن يتغير).
 */
class CustomerRequestAssignment extends Model
{
    protected $table = 'customer_request_assignments';

    /** @var list<string> */
    public const ACTIONS = ['assigned', 'reassigned', 'refused', 'completed', 'cancelled'];

    protected $fillable = [
        'id', 'customer_id', 'provider_id', 'provider_name',
        'acted_by', 'action', 'reason',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
