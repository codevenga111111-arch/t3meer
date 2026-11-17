<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * حذف الحساب نهائياً — DELETE /api/v1/me (Bearer) → 204
 * (SPEC 3.2.4 + القرار 1.10.3 + مهمة ClickUp #13).
 *
 * المنطق الكامل (بدون DB cascade) في `AccountDeletionService`: التوكنات،
 * السجلات، المفضلة/التقييمات، الطلبات، المحتوى، التسويق/التوثيق/الاشتراك
 * والصلاحيات، ثم صف `users`.
 *
 * `confirm: accepted` إلزامي — الحذف لا رجعة فيه (الكود القديم يconfirmSwal).
 */
class AccountController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        // قاعدة implicit: الفارغ أو القيمة غير الموافقة = 422 (confirm: accepted
        // بقيمها المقبولة true/1/yes — SPEC 3.2.4: { "confirm": true }).
        $request->validate(['confirm' => ['accepted']], [
            'confirm.accepted' => 'يجب تأكيد الحذف بإرسال confirm=true',
        ]);

        (new AccountDeletionService)->delete($request->user());

        return $this->apiNoContent('تم حذف الحساب نهائياً');
    }
}
