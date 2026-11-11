<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * صف `public_offices_view` (SPEC 1.3) — المكاتب الهندسية في الدليل والرئيسية
 * (5.2.2 + 5.2.12).
 *
 * ملاحظات:
 * - `name` واحد موحّد = اسم المكتب (القائمة كانت تقرأ office_name وصفحة
 *   التفاصيل تقرأ name — القرار في 5.2.12: رجّع name واحد).
 * - `views_count` اتشال لأنه مرادف total_visits (SPEC 1.3 ملاحظات).
 * - `website` مالوش عمود في الـ schema الجديد فمش مترجّع.
 */
class PublicOfficeResource extends PublicProfileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'whats_app' => $this->whats_app,
            'governorate' => $this->governorate,
            'experience_years' => $this->experience_years,
            'profile_image' => $this->profile_image,
            'average_rating' => (float) $this->average_rating,
            'reviews_count' => (int) $this->reviews_count,
            'total_visits' => (int) $this->total_visits,
            'selected_plan' => $this->planValue(),
            'is_premium' => $this->isPremium(),
            'services' => $this->servicesList(),
            'projects' => $this->projectsList(),
            'permissions' => $this->permissionsArray(),
            'count_of_projects' => (int) $this->count_of_projects,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'bio' => $this->bio,
            'open_hours' => $this->open_hours,
            'video' => $this->video,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
        ];
    }
}
