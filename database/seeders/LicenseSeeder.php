<?php

namespace Database\Seeders;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use App\Enums\SubscriptionStatus;
use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUsers();
        $this->createProducts();
        $this->createSubscriptionPlans();
        $this->createLicenses();
        $this->createDevices();
        $this->createActivationRequests();
        $this->createSubscriptions();
    }

    private function createUsers(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'user'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'role' => 'user'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($userData['role']);
        }
    }

    private function createProducts(): void
    {
        $products = [
            ['name' => 'Laravel Monitor Pro', 'slug' => 'laravel-monitor-pro', 'description' => 'Solusi monitoring profesional untuk aplikasi Laravel', 'repo' => 'devWebs01/laravel13-pos', 'repo_id' => 1233155058],
            ['name' => 'Analytics Dashboard', 'slug' => 'analytics-dashboard', 'description' => 'Dasbor analitik dan pelaporan waktu nyata', 'repo' => 'devWebs01/NW-Coffe', 'repo_id' => 1212716271],
            ['name' => 'API Gateway', 'slug' => 'api-gateway', 'description' => 'Manajemen API enterprise dan pembatasan kecepatan', 'repo' => null, 'repo_id' => null],
        ];

        foreach ($products as $productData) {
            $product = Product::where('slug', $productData['slug'])->orWhere('github_repo_id', $productData['repo_id'])->first();

            if ($product) {
                $product->update([
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'is_active' => true,
                    'github_repo_id' => $productData['repo_id'],
                    'github_repo_full_name' => $productData['repo'],
                    'github_repo_url' => $productData['repo'] ? "https://github.com/{$productData['repo']}" : null,
                    'github_default_branch' => 'main',
                ]);
            } else {
                Product::create([
                    'slug' => $productData['slug'],
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'is_active' => true,
                    'github_repo_id' => $productData['repo_id'],
                    'github_repo_full_name' => $productData['repo'],
                    'github_repo_url' => $productData['repo'] ? "https://github.com/{$productData['repo']}" : null,
                    'github_default_branch' => 'main',
                ]);
            }
        }
    }

    private function createSubscriptionPlans(): void
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

    private function createLicenses(): void
    {
        $users = User::role('user')->get();
        $products = Product::all();

        $statuses = [LicenseStatus::Active, LicenseStatus::Active, LicenseStatus::Active, LicenseStatus::Suspended, LicenseStatus::Expired];
        $modes = [LicenseMode::Online, LicenseMode::Online, LicenseMode::SemiOnline, LicenseMode::Offline];

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
                        'mode' => $modes[array_rand($modes)],
                        'max_devices' => 5,
                        'expires_at' => now()->addDays($plan->duration_days ?? 30),
                        'metadata' => [
                            'registered_at' => now()->subDays(rand(1, 90))->toIso8601String(),
                        ],
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
                    'mode' => LicenseMode::Online,
                    'max_devices' => 25,
                    'expires_at' => now()->addYear(),
                    'metadata' => [
                        'registered_at' => now()->toIso8601String(),
                    ],
                ]
            );
        }
    }

    private function createDevices(): void
    {
        $licenses = License::where('status', LicenseStatus::Active)->get();
        $platforms = ['windows', 'macos', 'linux', 'ios', 'android'];
        $deviceNames = ['PC Pengembangan', 'Laptop', 'Server', 'Produksi', 'Perangkat Seluler', 'Runner CI/CD'];

        foreach ($licenses as $license) {
            $deviceCount = rand(1, min(3, $license->max_devices));

            for ($i = 0; $i < $deviceCount; $i++) {
                Device::updateOrCreate(
                    [
                        'license_id' => $license->id,
                        'fingerprint' => Str::random(64),
                    ],
                    [
                        'license_id' => $license->id,
                        'name' => $deviceNames[array_rand($deviceNames)],
                        'fingerprint' => Str::random(64),
                        'platform' => $platforms[array_rand($platforms)],
                        'platform_version' => fake()->numerify('#.#.#'),
                        'app_version' => fake()->numerify('#.#.#'),
                        'ip_address' => fake()->ipv4(),
                        'last_seen_at' => now()->subDays(rand(0, 30)),
                    ]
                );
            }
        }
    }

    private function createActivationRequests(): void
    {
        $devices = Device::whereHas('license', function ($q) {
            $q->where('mode', '!=', 'offline');
        })->get();

        $pendingCount = 0;
        foreach ($devices as $device) {
            if ($pendingCount >= 5) {
                break;
            }
            if (rand(0, 10) < 3) {
                ActivationRequest::updateOrCreate(
                    ['license_id' => $device->license_id, 'device_id' => $device->id],
                    [
                        'license_id' => $device->license_id,
                        'device_id' => $device->id,
                        'code' => strtoupper(Str::random(8)),
                        'status' => ActivationRequestStatus::Pending,
                        'expires_at' => now()->addMinutes(30),
                    ]
                );
                $pendingCount++;
            }
        }

        $approvedDevices = Device::whereHas('license', function ($q) {
            $q->where('mode', '!=', 'offline');
        })->limit(3)->get();

        foreach ($approvedDevices as $device) {
            ActivationRequest::updateOrCreate(
                ['license_id' => $device->license_id, 'device_id' => $device->id],
                [
                    'license_id' => $device->license_id,
                    'device_id' => $device->id,
                    'code' => strtoupper(Str::random(8)),
                    'status' => ActivationRequestStatus::Approved,
                    'expires_at' => now()->addMinutes(30),
                    'activated_at' => now()->subDays(rand(1, 7)),
                ]
            );
        }
    }

    private function createSubscriptions(): void
    {
        $users = User::all();
        $plans = SubscriptionPlan::all();

        foreach ($users as $user) {
            $plan = $plans->random();
            Subscription::updateOrCreate(
                ['user_id' => $user->id, 'subscription_plan_id' => $plan->id],
                [
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'license_id' => $user->licenses()->first()?->id,
                    'stripe_subscription_id' => 'sub_'.Str::random(24),
                    'stripe_customer_id' => 'cus_'.Str::random(24),
                    'status' => SubscriptionStatus::Active,
                    'current_period_start_at' => now()->subMonth(),
                    'current_period_end_at' => now()->addMonth(),
                ]
            );
        }
    }
}
