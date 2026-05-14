<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ActivateDeviceRequest;
use App\Models\Device;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ActivationController extends ApiController
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    public function activate(ActivateDeviceRequest $request): JsonResponse
    {
        $license = License::where('key', $request->validated('license_key'))->first();

        if (! $license) {
            return $this->error('Invalid license key', 404);
        }

        $validation = $this->licenseService->validate($license);
        if (! $validation['valid']) {
            return $this->error($validation['reason'], 403);
        }

        $deviceData = $request->validated('device');

        $existingDevice = Device::where('fingerprint', $deviceData['fingerprint'])
            ->where('license_id', $license->id)
            ->first();

        if ($existingDevice) {
            if ($license->mode->requiresActivation()) {
                $activationRequest = $this->licenseService->createActivationRequest($existingDevice);

                if ($activationRequest) {
                    return $this->success([
                        'requires_approval' => true,
                        'activation_code' => $activationRequest->code,
                        'expires_at' => $activationRequest->expires_at->toIso8601String(),
                    ], 'Activation code generated');
                }

                return $this->error('Pending activation request exists', 409);
            }

            $existingDevice->update(['last_seen_at' => now()]);

            return $this->success([
                'device_id' => $existingDevice->id,
                'offline_until' => now()->addDays(7)->toIso8601String(),
            ], 'Device already activated');
        }

        if (! $this->licenseService->checkDeviceLimit($license)) {
            $device = $this->licenseService->registerDevice($license, $deviceData);
            $activationRequest = $this->licenseService->createActivationRequest($device);

            if ($activationRequest) {
                return $this->success([
                    'device_id' => $device->id,
                    'requires_approval' => true,
                    'activation_code' => $activationRequest->code,
                    'expires_at' => $activationRequest->expires_at->toIso8601String(),
                ], 'Device limit reached, activation required');
            }
        }

        $device = $this->licenseService->registerDevice($license, $deviceData);

        if ($license->mode->requiresActivation()) {
            $activationRequest = $this->licenseService->createActivationRequest($device);

            if ($activationRequest) {
                return $this->success([
                    'device_id' => $device->id,
                    'requires_approval' => true,
                    'activation_code' => $activationRequest->code,
                    'expires_at' => $activationRequest->expires_at->toIso8601String(),
                ], 'Device registered, activation required');
            }
        }

        return $this->success([
            'device_id' => $device->id,
            'offline_until' => now()->addDays(7)->toIso8601String(),
        ], 'Device activated successfully');
    }

    public function verify(string $key, string $fingerprint): JsonResponse
    {
        $license = License::where('key', $key)->first();

        if (! $license) {
            return $this->error('Invalid license key', 404);
        }

        $device = Device::where('fingerprint', $fingerprint)
            ->where('license_id', $license->id)
            ->first();

        if (! $device) {
            return $this->error('Device not registered', 404);
        }

        $result = $this->licenseService->verifyActivation($device, request('code', ''));

        return $this->success($result);
    }

    public function status(string $key, string $fingerprint): JsonResponse
    {
        $license = License::where('key', $key)->first();

        if (! $license) {
            return $this->error('Invalid license key', 404);
        }

        $device = Device::where('fingerprint', $fingerprint)
            ->where('license_id', $license->id)
            ->first();

        if (! $device) {
            return $this->error('Device not registered', 404);
        }

        $validation = $this->licenseService->validate($license);

        $activationRequest = $device->activationRequest()
            ->where('status', 'approved')
            ->orderByDesc('activated_at')
            ->first();

        $offlineUntil = null;
        if ($activationRequest) {
            $offlineUntil = $activationRequest->activated_at->addDays(7);
        } elseif ($device->last_seen_at?->isAfter(now()->subDays(7))) {
            $offlineUntil = $device->last_seen_at->addDays(7);
        }

        return $this->success([
            'license_valid' => $validation['valid'],
            'license_status' => $license->status->value,
            'device_activated' => $offlineUntil !== null && $offlineUntil->isFuture(),
            'offline_until' => $offlineUntil?->toIso8601String(),
        ]);
    }
}
