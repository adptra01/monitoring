<?php

namespace App\Services;

use App\Enums\ActivationRequestStatus;
use App\Enums\AuditAction;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;

class LicenseService
{
    public function __construct(
        private LicenseKeyService $licenseKeyService,
    ) {}

    public function validate(
        string $licenseKey,
        string $deviceId,
        ?string $appVersion = null,
    ): array {
        $license = License::query()->where('license_key', $licenseKey)->first();

        if (! $license) {
            return $this->invalidResponse('not_found', 'License key not found');
        }

        if ($license->status === LicenseStatus::Revoked->value) {
            return $this->invalidResponse('revoked', 'License has been revoked');
        }

        if ($license->status === LicenseStatus::Suspended->value) {
            return $this->invalidResponse('suspended', 'License is suspended');
        }

        if ($license->status === LicenseStatus::Expired->value || $license->expired_at->isPast()) {
            return $this->invalidResponse('expired', 'License has expired');
        }

        if (! $license->isDeviceBound($deviceId)) {
            return $this->invalidResponse('device_mismatch', 'Device is not registered with this license');
        }

        $license->devices()
            ->where('device_id', $deviceId)
            ->update([
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

        $cacheUntil = now()->addDays(7);
        $cacheTtl = now()->diffInSeconds($cacheUntil);

        AuditLog::log(
            action: AuditAction::LicenseValidated->value,
            payload: ['device_id' => $deviceId, 'app_version' => $appVersion],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'valid' => true,
            'status' => LicenseStatus::Active->value,
            'expired_at' => $license->expired_at->format('Y-m-d'),
            'cache_until' => $cacheUntil->format('Y-m-d'),
            'cache_ttl_seconds' => $cacheTtl,
            'server_time' => now()->toIso8601String(),
            'message' => 'License valid',
        ];
    }

    public function activate(
        string $licenseKey,
        string $deviceId,
        string $deviceName,
    ): array {
        $license = License::query()->where('license_key', $licenseKey)->first();

        if (! $license) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'License key not found',
            ];
        }

        if (! $license->isActive()) {
            $status = $license->status === LicenseStatus::Expired->value || $license->expired_at->isPast()
                ? 'expired'
                : $license->status;

            $messages = [
                'revoked' => 'License is revoked',
                'suspended' => 'License is suspended',
                'expired' => 'License has expired',
            ];

            return [
                'success' => false,
                'status' => $status,
                'message' => $messages[$status] ?? 'License is not active',
            ];
        }

        $existingDevice = $license->devices()
            ->where('device_id', $deviceId)
            ->first();

        if ($existingDevice) {
            if ($existingDevice->is_active) {
                $existingDevice->update([
                    'last_seen_at' => now(),
                    'ip_address' => request()->ip(),
                ]);

                AuditLog::log(
                    action: AuditAction::LicenseActivated->value,
                    payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                    license: $license,
                    ipAddress: request()->ip(),
                );

                return [
                    'success' => true,
                    'status' => 'active',
                    'message' => 'Device already activated',
                    'expired_at' => $license->expired_at->format('Y-m-d'),
                ];
            }

            $existingDevice->update([
                'is_active' => true,
                'last_seen_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device reactivated',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        if ($license->canActivateDevice()) {
            $license->devices()->create([
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'ip_address' => request()->ip(),
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);

            if (! $license->activated_at) {
                $license->update(['activated_at' => now()]);
            }

            AuditLog::log(
                action: AuditAction::DeviceBound->value,
                payload: ['device_id' => $deviceId, 'device_name' => $deviceName],
                license: $license,
                ipAddress: request()->ip(),
            );

            return [
                'success' => true,
                'status' => 'active',
                'message' => 'Device activated successfully',
                'expired_at' => $license->expired_at->format('Y-m-d'),
            ];
        }

        $existingDeviceIds = $license->activeDevices()
            ->pluck('device_id')
            ->toArray();

        $request = $license->activationRequests()->create([
            'old_device_id' => $existingDeviceIds[0] ?? null,
            'new_device_id' => $deviceId,
            'new_device_name' => $deviceName,
            'ip_address' => request()->ip(),
            'status' => ActivationRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        AuditLog::log(
            action: AuditAction::ActivationRequested->value,
            payload: [
                'activation_request_id' => $request->id,
                'old_device_id' => $request->old_device_id,
                'new_device_id' => $deviceId,
            ],
            license: $license,
            ipAddress: request()->ip(),
        );

        return [
            'success' => false,
            'status' => 'pending_approval',
            'message' => 'Device limit reached. Approval request sent to admin.',
            'activation_request_id' => $request->id,
        ];
    }

    public function checkUpdate(string $licenseKey, string $currentVersion): array
    {
        $licenseExists = License::query()
            ->where('license_key', $licenseKey)
            ->where('status', LicenseStatus::Active->value)
            ->where('expired_at', '>', now())
            ->exists();

        if (! $licenseExists) {
            return [
                'update_available' => false,
                'latest_version' => $currentVersion,
                'download_url' => null,
                'message' => 'Unable to check updates',
                'release_notes' => null,
            ];
        }

        return [
            'update_available' => false,
            'latest_version' => $currentVersion,
            'download_url' => null,
            'message' => 'You are using the latest version',
            'release_notes' => null,
        ];
    }

    private function invalidResponse(string $status, string $message): array
    {
        return [
            'valid' => false,
            'status' => $status,
            'cache_until' => null,
            'cache_ttl_seconds' => 0,
            'server_time' => now()->toIso8601String(),
            'message' => $message,
        ];
    }
}
