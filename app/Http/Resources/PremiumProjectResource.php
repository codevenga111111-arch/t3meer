<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * صف `premium_active_view` (SPEC 1.3 + 5.2.5) — مشاريع الباقات المميزة
 * النشطة اللي بتعرضها الصفحة الرئيسية.
 *
 * `details` بيرجع object (cast على Project) زي ما كان الـ view JSON بيرجّعه.
 */
class PremiumProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'location' => $this->location,
            'area' => $this->area,
            'type' => $this->type,
            'image' => $this->image,
            'slug' => $this->slug,
            'user_type' => $this->user_type,
            'user_name' => $this->user_name,
            'details' => $this->details,
            'created_at' => $this->created_at,
        ];
    }
}
