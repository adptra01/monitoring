<?php

namespace App\Services;

use App\Events\DeviceDeactivated;
use App\Events\DeviceRegistered;
use App\Models\Device;
use App\Models\License;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class DeviceService
{
    public function register(License $license, array $data): Device
    {
        $data['fingerprint'] = $data['fingerprint'] ?? Str::random(64);

        $device = $license->devices()->create($data);

        Event::dispatch(new DeviceRegistered($device));

        return $device;
    }

    public function checkDeviceLimit(License $license): bool
    {
        return $license->devices()->count() < $license->max_devices;
    }

    public function findByFingerprint(License $license, string $fingerprint): ?Device
    {
        return $license->devices()->where('fingerprint', $fingerprint)->first();
    }

    public function touch(Device $device): void
    {
        $device->update(['last_seen_at' => now()]);
    }

    public function deactivate(Device $device): void
    {
        $device->update(['is_active' => false]);

        Event::dispatch(new DeviceDeactivated($device));
    }
}
