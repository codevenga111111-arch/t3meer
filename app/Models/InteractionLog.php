<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل التفاعلات الإدارية (SPEC 1.2.21).
 *
 * يُكتب عند قبول/رفض التوثيق فقط (admin) — القرار: بُني كسجل حقيقي
 * بدل حذفه (كان لا يُقرأ في الكود القديم، لكنه يخدم audit log المقترح
 * في مهمة 31).
 */
class InteractionLog extends Model
{
    protected $table = 'interaction_logs';

    protected $fillable = [
        'id', 'user_id', 'activity_type', 'activity_text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
