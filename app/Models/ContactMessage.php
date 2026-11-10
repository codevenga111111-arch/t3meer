<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * رسائل "اتصل بنا" (قرار 7.1#9 — مهمة ClickUp #6).
 *
 * ⛔ الكود القديم لم يكن يحفظ أي شيء — كان ينتظر 1.5 ثانية ويعرض نجاحاً.
 * الآن جدول حقيقي + إشعار إيميل للإدارة (SPEC 5.3.4).
 */
class ContactMessage extends Model
{
    protected $table = 'contact_messages';

    /** @var list<string> */
    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    protected $fillable = [
        'id', 'name', 'phone', 'email', 'subject', 'message', 'status',
    ];
}
