<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * صف `marketing_active_users` (SPEC 1.3 + 5.2.10) — بانرات الدليل.
 *
 * `banner` = {title, description, image} (cast على Marketing) والفلاتر
 * (user_type + plan_type) بتضيفها العميل على الـ scope.
 */
class MarketingBannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'banner' => $this->banner,
            'slug' => $this->slug,
            'user_type' => $this->user_type,
            'plan_type' => $this->plan_type,
        ];
    }
}
