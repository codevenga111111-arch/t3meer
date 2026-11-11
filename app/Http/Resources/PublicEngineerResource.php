<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * صف `public_engineers_view` (SPEC 1.3) — المهندسين في الدليل والرئيسية
 * (5.2.3 + 5.2.11).
 *
 * ملاحظات:
 * - `name` = اسم المهندس (ملف) و`user_name` = اسم حساب المستخدم (users.name) —
 *   الاتنين في الـ view والكود القديم كان يقرأهم في البحث.
 * - `city`/`website` أعمدة حقيقية في الملف ومش في قائمة الأعمدة المقروءة
 *   للـ view، مترجّعة لأنها متاحة في الـ schema الجديد.
 */
class PublicEngineerResource extends PublicProfileResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'user_name' => $this->user_name,
            'major' => $this->major,
            'phone' => $this->phone,
            'email' => $this->email,
            'whats_app' => $this->whats_app,
            'website' => $this->website,
            'governorate' => $this->governorate,
            'city' => $this->city,
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
            'skills' => $this->skills,
            'bio' => $this->bio,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'youtube' => $this->youtube,
        ];
    }
}
