<?php

namespace Database\Factories;

use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'key' => License::generateKey(),
            'status' => LicenseStatus::Active,
            'mode' => LicenseMode::Online,
            'max_devices' => fake()->numberBetween(1, 5),
            'expires_at' => now()->addYear(),
            'metadata' => [],
        ];
    }

    public function withSubscriptionPlan(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan_id' => SubscriptionPlan::factory(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LicenseStatus::Suspended]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LicenseStatus::Expired]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => ['mode' => LicenseMode::Offline]);
    }

    public function semiOnline(): static
    {
        return $this->state(fn (array $attributes) => ['mode' => LicenseMode::SemiOnline]);
    }
}
