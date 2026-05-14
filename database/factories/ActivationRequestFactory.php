<?php

namespace Database\Factories;

use App\Enums\ActivationRequestStatus;
use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivationRequestFactory extends Factory
{
    protected $model = ActivationRequest::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'device_id' => Device::factory(),
            'code' => ActivationRequest::generateCode(),
            'status' => ActivationRequestStatus::Pending,
            'expires_at' => now()->addMinutes(30),
            'activated_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivationRequestStatus::Approved,
            'activated_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivationRequestStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivationRequestStatus::Expired,
            'expires_at' => now()->subMinutes(30),
        ]);
    }
}