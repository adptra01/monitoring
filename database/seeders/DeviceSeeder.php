<?php

namespace Database\Seeders;

use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $licenses = License::where('status', LicenseStatus::Active)->get();
        $platforms = ['windows', 'macos', 'linux', 'ios', 'android'];
        $deviceNames = ['PC Pengembangan', 'Laptop', 'Server', 'Produksi', 'Perangkat Seluler', 'Runner CI/CD'];

        foreach ($licenses as $license) {
            $deviceCount = rand(1, min(3, $license->max_devices));
            $devices = [];

            for ($i = 0; $i < $deviceCount; $i++) {
                $devices[] = [
                    'name' => $deviceNames[array_rand($deviceNames)],
                    'fingerprint' => Str::random(64),
                    'platform' => $platforms[array_rand($platforms)],
                    'platform_version' => fake()->numerify('#.#.#'),
                    'app_version' => fake()->numerify('#.#.#'),
                    'ip_address' => fake()->ipv4(),
                    'last_seen_at' => now()->subDays(rand(0, 30))->toIso8601String(),
                ];
            }

            $license->update(['devices' => $devices]);
        }
    }
}
