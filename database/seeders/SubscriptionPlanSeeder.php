<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['product' => 'laravel-monitor-pro', 'name' => 'Starter', 'slug' => 'starter', 'days' => 30],
            ['product' => 'laravel-monitor-pro', 'name' => 'Professional', 'slug' => 'professional', 'days' => 90],
            ['product' => 'laravel-monitor-pro', 'name' => 'Enterprise', 'slug' => 'enterprise', 'days' => 365],
            ['product' => 'analytics-dashboard', 'name' => 'Basic', 'slug' => 'basic', 'days' => 30],
            ['product' => 'analytics-dashboard', 'name' => 'Premium', 'slug' => 'premium', 'days' => 90],
            ['product' => 'api-gateway', 'name' => 'Team', 'slug' => 'team', 'days' => 365],
        ];

        foreach ($plans as $planData) {
            $product = Product::where('slug', $planData['product'])->first();
            if (! $product) {
                continue;
            }

            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                [
                    'product_id' => $product->id,
                    'name' => $planData['name'],
                    'slug' => $planData['slug'],
                    'description' => $planData['name'].' plan for '.$product->name,
                    'duration_days' => $planData['days'],
                    'is_active' => true,
                ]
            );
        }
    }
}
