<?php

namespace App\Http\Resources;

use App\Support\Onboarding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * بيانات المستخدم في ردود auth (SPEC 1.5.7 + 2.0.1 + 2.V.6 + مهمة ClickUp #11).
 *
 * الحقول المقروءة من `user_metadata` في الكود القديم بقت أعمدة `users` هنا.
 * حقول الـ onboarding الإضافية (2.V.6):
 * - `is_verified`          = صف توثيق بحالة active (لا يوجد عمود users.is_verified).
 * - `validation_required`  = النوع يتطلب توثيقاً ولم يُرسل بعد (engineer/office).
 * - `validation_status`    = null لو مااتبعتش (المستخدم شايف "مطلوب توثيق").
 * - `next_route`           = الفرونت يمشي عليه ولا يكرر منطق getStepRoute() (2.V.2).
 *
 * ⛔ `next_route` مبني على `current_step` فقط — التأكيد مايرفعش الخطوة
 * (2.V.2/2.V.7#2) عشان المهندس/المكتب يرجعوا `/validation/{type}`.
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $type = $user->userType();
        $step = (int) $user->getAttribute('current_step');
        $validation = $user->validation;

        return [
            'id' => $user->id,
            'email' => $user->email,
            'user_type' => $type->value,
            'current_step' => $step,
            'slug' => $user->slug,
            'name' => $user->name,
            'phone' => $user->phone,
            'selected_plan' => $user->selected_plan?->value,
            'selected_billing_cycle' => $user->selected_billing_cycle,
            'status' => $user->status?->value,
            'profile_image' => $user->profile_image,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'rejection_reason' => $user->rejection_reason,

            // — حقول 2.V.6 —
            'is_verified' => $validation !== null && $validation->isApproved(),
            'validation_required' => $type->requiresValidation() && $validation === null,
            'validation_status' => $validation?->getAttribute('status')?->value,
            'validation_rejection_reason' => $validation?->rejection_reason,
            'next_route' => Onboarding::stepRoute($type->value, $step),
        ];
    }
}
