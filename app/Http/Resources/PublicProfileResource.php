<?php

namespace App\Http\Resources;

use App\Enums\PlanType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * قاعدة مشتركة لـ API Resources بتاعة الـ public views (SPEC 1.3 + 5.2.x).
 *
 * - المجموعات اللي جاية من الـ select كـ JSON (services/projects/permissions)
 *   بتفك من string إلى array.
 * - أعلام user_permissions لازم ترجع boolean حقيقي: الـ JSON بيطلعها 0/1
 *   (tinyint) والواجهة بتقارن بـ `=== false` (SPEC 1.7.5).
 * - selected_plan بيرجع PlanType enum بسبب cast على User — بيتحوّل لنص هنا.
 */
abstract class PublicProfileResource extends JsonResource
{
    /** أعلام user_permissions boolean (SPEC 1.7.5). */
    protected const PERMISSION_FLAGS = [
        'whatsapp_tracking',
        'call_tracking',
        'detailed_analytics',
        'video_projects',
        'google_maps_location',
        'directory_ad_banner',
        'distinguished_badge',
        'homepage_ad_banner',
        'social_media_promo',
        'facebook_pixel',
    ];

    /** قيمة الباقة نصاً ('free'|'basic'|'professional'|'premium'). */
    protected function planValue(): ?string
    {
        $plan = $this->selected_plan;

        return $plan instanceof PlanType ? $plan->value : $plan;
    }

    protected function isPremium(): bool
    {
        return $this->planValue() === PlanType::Premium->value;
    }

    /**
     * فك عمود JSON اللي جاي من الـ select (string على MySQL وSQLite).
     *
     * @param  array<int|string, mixed>  $default
     * @return array<int|string, mixed>
     */
    protected function decodeJson(mixed $value, array $default = []): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return $default;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $default;
    }

    /**
     * صلاحية الاشتراك كـ object، أو null لو ما فيش صف للمستخدم
     * (صفوف الـ template بـ user_id = NULL مش بتتحسب — SPEC 1.2.13).
     *
     * @return array<string, mixed>|null
     */
    protected function permissionsArray(): ?array
    {
        $value = $this->permissions;

        if ($value === null) {
            return null;
        }

        $permissions = $this->decodeJson($value);

        foreach (self::PERMISSION_FLAGS as $flag) {
            if (array_key_exists($flag, $permissions)) {
                $permissions[$flag] = (bool) $permissions[$flag];
            }
        }

        return $permissions;
    }

    /**
     * عدد مصفوفة JSON مفكوعة.
     *
     * @param  array<int|string, mixed>  $items
     */
    protected function countOf(array $items): int
    {
        return count($items);
    }

    /** @return array<int, mixed> */
    protected function projectsList(): array
    {
        return $this->decodeJson($this->projects);
    }

    /** @return array<int, mixed> */
    protected function servicesList(): array
    {
        return $this->decodeJson($this->services);
    }

    /** @return array<int, mixed> */
    protected function productsList(): array
    {
        return $this->decodeJson($this->products);
    }
}
