<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ValidateLicenseRequest;
use App\Models\License;
use App\Services\DeviceService;
use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use App\Services\OfflineTokenService;
use Illuminate\Http\JsonResponse;

class ValidationController extends ApiController
{
    public function __construct(
        protected LicenseService $licenseService,
        protected LicenseKeyService $keyService,
        protected DeviceService $deviceService,
        protected OfflineTokenService $offlineTokenService,
    ) {}

    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $license = License::where('key', $request->validated('license_key'))->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $validation = $this->licenseService->validate($license);

        if (! $validation['valid']) {
            return $this->error($validation['reason'], 403);
        }

        $fingerprint = $request->validated('device.fingerprint');

        $device = $this->deviceService->findByFingerprint($license, $fingerprint);

        if (! $device) {
            return $this->error('Perangkat tidak terdaftar', 403);
        }

        $this->deviceService->touch($device);

        $tokenData = $this->offlineTokenService->issue($license, $device);

        return $this->success([
            'valid' => true,
            'status' => $license->status->value,
            'license_key' => $this->keyService->mask($license->key),
            'product' => $license->product->name,
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'max_devices' => $license->max_devices,
            'devices_count' => $license->devices()->count(),
            'cache_until' => now()->addDays(7)->format('Y-m-d'),
            'offline_token' => $tokenData['token'],
            'message' => 'Lisensi valid',
        ]);
    }
}
