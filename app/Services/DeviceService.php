<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\License;
use Illuminate\Support\Str;

class DeviceService
{
    public function register(License $license, array $data): Device
    {
        $data['fingerprint'] = $data['fingerprint'] ?? Str::random(64);

        $device = $license->devices()->create($data);

        AuditLog::create([
            'action' => 'device_registered',
            'entity_type' => License::class,
            'entity_id' => $license->id,
            'new_values' => ['device_id' => $device->id],
            'created_at' => now(),
        ]);

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

        AuditLog::create([
            'action' => 'device_deactivated',
            'entity_type' => License::class,
            'entity_id' => $device->license_id,
            'new_values' => ['device_id' => $device->id],
            'created_at' => now(),
        ]);
    }
}
