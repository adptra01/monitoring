<?php

namespace Database\Seeders;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TeamRole;
use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use App\Models\Membership;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createAdminUser();
        $this->createRegularUsers();

        $this->createProducts();
        $this->createSubscriptionPlans();
        $this->createLicenses();
        $this->createDevices();
        $this->createActivationRequests();
        $this->createSubscriptions();
    }

    private function createAdminUser(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );
        $admin->assignRole('admin');

        $this->createTeamForUser($admin, 'Admin Team');
    }

    private function createRegularUsers(): void
    {
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                ]
            );
            $user->assignRole('user');

            $this->createTeamForUser($user, $userData['name']."'s Team");
        }
    }

    private function createTeamForUser(User $user, string $teamName): void
    {
        $team = Team::updateOrCreate(
            ['name' => $teamName],
            [
                'name' => $teamName,
                'slug' => Str::slug($teamName),
                'is_personal' => true,
            ]
        );

        $user->current_team_id = $team->id;
        $user->save();

        Membership::updateOrCreate(
            ['user_id' => $user->id, 'team_id' => $team->id],
            ['role' => TeamRole::Owner]
        );
    }

    private function createProducts(): void
    {
        $products = [
            ['name' => 'Laravel Monitor Pro', 'slug' => 'laravel-monitor-pro', 'description' => 'Professional monitoring solution for Laravel applications'],
            ['name' => 'Analytics Dashboard', 'slug' => 'analytics-dashboard', 'description' => 'Real-time analytics and reporting dashboard'],
            ['name' => 'API Gateway', 'slug' => 'api-gateway', 'description' => 'Enterprise API management and rate limiting'],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'name' => $productData['name'],
                    'slug' => $productData['slug'],
                    'description' => $productData['description'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function createSubscriptionPlans(): void
    {
        $plans = [
            ['product' => 'laravel-monitor-pro', 'name' => 'Starter', 'slug' => 'starter', 'monthly' => 9.99, 'yearly' => 99.99, 'devices' => 1],
            ['product' => 'laravel-monitor-pro', 'name' => 'Professional', 'slug' => 'professional', 'monthly' => 29.99, 'yearly' => 299.99, 'devices' => 5],
            ['product' => 'laravel-monitor-pro', 'name' => 'Enterprise', 'slug' => 'enterprise', 'monthly' => 99.99, 'yearly' => 999.99, 'devices' => 25],
            ['product' => 'analytics-dashboard', 'name' => 'Basic', 'slug' => 'basic', 'monthly' => 14.99, 'yearly' => 149.99, 'devices' => 2],
            ['product' => 'analytics-dashboard', 'name' => 'Premium', 'slug' => 'premium', 'monthly' => 49.99, 'yearly' => 499.99, 'devices' => 10],
            ['product' => 'api-gateway', 'name' => 'Team', 'slug' => 'team', 'monthly' => 79.99, 'yearly' => 799.99, 'devices' => 15],
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
                    'monthly_price' => $planData['monthly'],
                    'yearly_price' => $planData['yearly'],
                    'max_devices' => $planData['devices'],
                    'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
                    'is_active' => true,
                    'is_default' => $planData['slug'] === 'professional' || $planData['slug'] === 'basic',
                ]
            );
        }
    }

    private function createLicenses(): void
    {
        $users = User::where('is_admin', false)->get();
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
                        'max_devices' => $plan->max_devices,
                        'expires_at' => now()->addMonths(rand(1, 12)),
                        'metadata' => [
                            'registered_at' => now()->subDays(rand(1, 90))->toIso8601String(),
                        ],
                    ]
                );
            }
        }

        $admin = User::where('is_admin', true)->first();
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
        $deviceNames = ['Development PC', 'Laptop', 'Server', 'Production Box', 'Mobile Device', 'CI/CD Runner'];

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
