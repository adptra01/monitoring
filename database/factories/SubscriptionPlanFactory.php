<?php

namespace Database\Factories;

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
            'name' => ucwords($name).' Plan',
            'slug' => Str::slug($name.'-plan'),
            'description' => fake()->sentence(),
            'duration_days' => fake()->numberBetween(30, 365),
            'is_active' => true,
        ];
    }
}
