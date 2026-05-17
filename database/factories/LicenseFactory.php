<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
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
            'max_devices' => fake()->numberBetween(1, 5),
            'expires_at' => now()->addYear(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_store' => fake()->company(),
            'customer_address' => fake()->address(),
            'starts_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LicenseStatus::Suspended]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LicenseStatus::Expired]);
    }
}
