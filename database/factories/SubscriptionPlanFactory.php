<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'name' => fake()->randomElement(['Monthly', 'Yearly', 'Quarterly']),
            'duration_days' => fake()->randomElement([30, 90, 365]),
            'price' => fake()->randomFloat(2, 50000, 5000000),
            'is_active' => true,
        ];
    }
}
