<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ActivateDeviceRequest;
use App\Models\License;
use App\Services\ActivationService;
use App\Services\DeviceService;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ActivationController extends ApiController
{
    public function __construct(
        protected LicenseService $licenseService,
        protected DeviceService $deviceService,
        protected ActivationService $activationService,
    ) {}

    public function activate(ActivateDeviceRequest $request): JsonResponse
    {
        $license = License::where('key', $request->validated('license_key'))->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $validation = $this->licenseService->validate($license);
        if (! $validation['valid']) {
            return $this->error($validation['reason'], 403);
        }

        $deviceData = $request->validated('device');

        $existingDevice = $this->deviceService->findByFingerprint($license, $deviceData['fingerprint']);

        if ($existingDevice) {
            if ($license->mode->requiresActivation()) {
                $activationRequest = $this->activationService->createRequest($existingDevice);

                if ($activationRequest) {
                    return $this->success([
                        'requires_approval' => true,
                        'activation_code' => $activationRequest->code,
                        'expires_at' => $activationRequest->expires_at->toIso8601String(),
                    ], 'Kode aktivasi dibuat');
                }

                return $this->error('Permintaan aktivasi tertunda sudah ada', 409);
            }

            $this->deviceService->touch($existingDevice);

            return $this->success([
                'device_id' => $existingDevice->id,
                'offline_until' => now()->addDays(7)->toIso8601String(),
            ], 'Perangkat sudah diaktifkan');
        }

        $device = $this->deviceService->register($license, $deviceData);

        if (! $this->deviceService->checkDeviceLimit($license) || $license->mode->requiresActivation()) {
            $activationRequest = $this->activationService->createRequest($device);

            if ($activationRequest) {
                return $this->success([
                    'device_id' => $device->id,
                    'requires_approval' => true,
                    'activation_code' => $activationRequest->code,
                    'expires_at' => $activationRequest->expires_at->toIso8601String(),
                ], ! $this->deviceService->checkDeviceLimit($license)
                    ? 'Batas perangkat tercapai, aktivasi diperlukan'
                    : 'Perangkat terdaftar, aktivasi diperlukan');
            }
        }

        return $this->success([
            'device_id' => $device->id,
            'offline_until' => now()->addDays(7)->toIso8601String(),
        ], 'Perangkat berhasil diaktifkan');
    }

    public function verify(string $key, string $fingerprint): JsonResponse
    {
        $license = License::where('key', $key)->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $device = $this->deviceService->findByFingerprint($license, $fingerprint);

        if (! $device) {
            return $this->error('Perangkat tidak terdaftar', 404);
        }

        $result = $this->activationService->verifyActivation($device, request('code', ''));

        return $this->success($result);
    }

    public function status(string $key, string $fingerprint): JsonResponse
    {
        $license = License::where('key', $key)->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $device = $this->deviceService->findByFingerprint($license, $fingerprint);

        if (! $device) {
            return $this->error('Perangkat tidak terdaftar', 404);
        }

        $validation = $this->licenseService->validate($license);
        $offlineUntil = $this->activationService->calculateOfflineUntil($device);

        return $this->success([
            'license_valid' => $validation['valid'],
            'license_status' => $license->status->value,
            'device_activated' => $offlineUntil !== null && $offlineUntil->isFuture(),
            'offline_until' => $offlineUntil?->toIso8601String(),
        ]);
    }
}
