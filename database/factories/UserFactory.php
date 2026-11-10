<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            // id بيولّده Model::booted() — ممنوع إرساله من body (SPEC 0.4).
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'slug' => Slug::fromEmail(fake()->unique()->safeEmail()),
            'phone' => '010'.fake()->unique()->numerify('########'),
            'user_type' => 'engineer',
            'current_step' => 1,
            'status' => 'active',
            'selected_plan' => 'free',
        ];
    }

    /** مستخدم وصل لخطوة Onboarding الأخيرة (قرار #12 — المسار 2.A). */
    public function onboardingComplete(): static
    {
        return $this->state(fn (array $attributes) => ['current_step' => 4, 'status' => 'active']);
    }

    /** أدمن — الدور مسجّل في user_roles (بيانات البروداكشن). */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['user_type' => 'admin']);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
