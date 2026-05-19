<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'slug' => 'starter', 'days' => 30],
            ['name' => 'Professional', 'slug' => 'professional', 'days' => 90],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'days' => 365],
            ['name' => 'Basic', 'slug' => 'basic', 'days' => 30],
            ['name' => 'Premium', 'slug' => 'premium', 'days' => 90],
            ['name' => 'Team', 'slug' => 'team', 'days' => 365],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'name' => $planData['name'],
                    'slug' => $planData['slug'],
                    'description' => $planData['name'].' plan',
                    'duration_days' => $planData['days'],
                    'is_active' => true,
                ]
            );
        }
    }
}
