<?php

namespace Database\Seeders;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('user')->get();
        $products = Product::all();

        $statuses = [LicenseStatus::Active, LicenseStatus::Active, LicenseStatus::Active, LicenseStatus::Suspended, LicenseStatus::Expired];

        foreach ($users as $user) {
            foreach ($products->random(rand(1, 2)) as $product) {
                $plan = $product->subscriptionPlans()->inRandomOrder()->first();
                if (! $plan) {
                    continue;
                }

                License::updateOrCreate(
                    ['key' => License::generateKey()],
                    [
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'subscription_plan_id' => $plan->id,
                        'key' => License::generateKey(),
                        'status' => $statuses[array_rand($statuses)],
                        'max_devices' => 5,
                        'starts_at' => now(),
                        'expires_at' => now()->addDays($plan->duration_days ?? 30),
                        'customer_name' => $user->name,
                        'customer_phone' => fake()->phoneNumber(),
                        'customer_store' => fake()->company(),
                    ]
                );
            }
        }

        $admin = User::role('admin')->first();
        $proProduct = Product::where('slug', 'laravel-monitor-pro')->first();
        $proPlan = $proProduct?->subscriptionPlans()->where('slug', 'enterprise')->first();

        if ($admin && $proProduct && $proPlan) {
            License::updateOrCreate(
                ['key' => License::generateKey()],
                [
                    'product_id' => $proProduct->id,
                    'user_id' => $admin->id,
                    'subscription_plan_id' => $proPlan->id,
                    'key' => License::generateKey(),
                    'status' => LicenseStatus::Active,
                    'max_devices' => 25,
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                    'customer_name' => $admin->name,
                ]
            );
        }
    }
}
