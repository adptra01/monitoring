<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->email(),
            'license_key' => 'LIC-'.strtoupper(Str::random(8)).'-'.strtoupper(Str::random(8)),
            'status' => LicenseStatus::Active,
            'max_devices' => 1,
            'started_at' => now()->subMonth(),
            'expired_at' => now()->addYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Active,
            'expired_at' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Expired,
            'expired_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Suspended,
            'expired_at' => now()->addYear(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => LicenseStatus::Revoked,
            'expired_at' => now()->addYear(),
        ]);
    }

    public function withMaxDevices(int $max = 3): static
    {
        return $this->state(fn () => ['max_devices' => $max]);
    }

    public function withDevices(int $count = 1): static
    {
        return $this->has(DeviceFactory::new()->count($count), 'devices');
    }
}
