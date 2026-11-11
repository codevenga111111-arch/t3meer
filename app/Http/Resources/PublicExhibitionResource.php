<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * صف `public_exhibitions_view` (SPEC 1.3) — المعارض والموردين في الدليل
 * (5.2.13) — وهو بديل `public_materials_view` الملغى (القرار 0.5).
 *
 * ملاحظات:
 * - `name` واحد موحّد = اسم المعرض (القائمة كانت تقرأ exhibition_name أو name).
 * - `count_of_projects` بيتحسب من مصفوفة projects لأن المعرض ما عندهش العمود
 *   اليدوي اللي عنده المكتب.
 * - `views_count` اتشال لأنه مرادف total_visits.
 */
class PublicExhibitionResource extends PublicProfileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $projects = $this->projectsList();

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'main_category' => $this->main_category,
            'governorate' => $this->governorate,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'whats_app' => $this->whats_app,
            'logo' => $this->logo,
            'profile_image' => $this->profile_image,
            'banner' => $this->banner,
            'experience_years' => $this->experience_years,
            'count_of_categories' => $this->count_of_categories,
            'count_of_projects' => $this->countOf($projects),
            'average_rating' => (float) $this->average_rating,
            'reviews_count' => (int) $this->reviews_count,
            'total_visits' => (int) $this->total_visits,
            'selected_plan' => $this->planValue(),
            'is_premium' => $this->isPremium(),
            'services' => $this->servicesList(),
            'products' => $this->productsList(),
            'projects' => $projects,
            'permissions' => $this->permissionsArray(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'open_hours' => $this->open_hours,
            'bio' => $this->bio,
            'video' => $this->video,
            'brands' => $this->brands,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
        ];
    }
}
