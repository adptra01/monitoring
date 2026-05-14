<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ValidateLicenseRequest;
use App\Models\License;
use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ValidationController extends ApiController
{
    public function __construct(
        protected LicenseService $licenseService,
        protected LicenseKeyService $keyService
    ) {}

    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $license = License::where('key', $request->validated('license_key'))->first();

        if (! $license) {
            return $this->error('Invalid license key', 404);
        }

        $validation = $this->licenseService->validate($license);

        if (! $validation['valid']) {
            return $this->error($validation['reason'], 403);
        }

        $fingerprint = $request->validated('device.fingerprint');

        $device = $license->devices()->where('fingerprint', $fingerprint)->first();

        if (! $device) {
            return $this->error('Device not registered', 403);
        }

        $this->licenseService->verifyActivation($device, '');

        $device->update(['last_seen_at' => now()]);

        return $this->success([
            'valid' => true,
            'license_key' => $this->keyService->mask($license->key),
            'product' => $license->product->name,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'max_devices' => $license->max_devices,
            'devices_count' => $license->devices()->count(),
        ]);
    }
}
