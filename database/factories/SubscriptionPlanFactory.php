<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'product_id' => Product::factory(),
            'name' => ucwords($name).' Plan',
            'slug' => Str::slug($name.'-plan'),
            'description' => fake()->sentence(),
            'monthly_price' => fake()->randomFloat(2, 9.99, 99.99),
            'yearly_price' => fake()->randomFloat(2, 99.99, 999.99),
            'stripe_price_id_monthly' => 'price_'.Str::random(24),
            'stripe_price_id_yearly' => 'price_'.Str::random(24),
            'max_devices' => fake()->numberBetween(1, 10),
            'features' => ['feature1', 'feature2', 'feature3'],
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
