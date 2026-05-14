<?php

namespace Database\Factories;

use App\Enums\ActivationRequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'new_device_id' => (string) Str::uuid(),
            'new_device_name' => fake()->word(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ActivationRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ActivationRequestStatus::Approved,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ActivationRequestStatus::Rejected,
            'handled_at' => now(),
            'handled_by' => UserFactory::new(),
        ]);
    }
}
