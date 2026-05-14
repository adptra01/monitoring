<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'name' => fake()->words(2, true),
            'fingerprint' => Str::random(64),
            'platform' => fake()->randomElement(['windows', 'macos', 'linux', 'ios', 'android']),
            'platform_version' => fake()->numerify('#.#.#'),
            'app_version' => fake()->numerify('#.#.#'),
            'ip_address' => fake()->ipv4(),
            'last_seen_at' => now(),
        ];
    }
}