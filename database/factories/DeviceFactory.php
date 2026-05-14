<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => (string) Str::uuid(),
            'device_name' => fake()->word().' '.fake()->randomNumber(3),
            'activated_at' => now(),
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }
}
