<?php

namespace App\Http\Controllers\Api;

use App\Models\License;
use App\Services\DeviceService;
use App\Services\OfflineTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends ApiController
{
    public function __construct(
        protected OfflineTokenService $offlineTokenService,
        protected DeviceService $deviceService,
    ) {}

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'min:5', 'max:64'],
            'device' => ['required', 'array'],
            'device.fingerprint' => ['required', 'string', 'min:32', 'max:64'],
            'offline_token' => ['required', 'string'],
        ]);

        $license = License::where('key', $validated['license_key'])->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $validation = $this->offlineTokenService->verify(
            $validated['offline_token'],
            $validated['device']['fingerprint'],
        );

        if (! $validation['valid']) {
            return $this->error($validation['reason'], 403);
        }

        $device = $this->deviceService->findByFingerprint($license, $validated['device']['fingerprint']);

        if (! $device) {
            return $this->error('Perangkat tidak terdaftar', 403);
        }

        $refreshed = $this->offlineTokenService->refresh($license, $device, $validated['offline_token']);

        if (! isset($refreshed['token'])) {
            return $this->error($refreshed['reason'] ?? 'Gagal memperbarui token', 403);
        }

        return $this->success([
            'offline_token' => $refreshed['token'],
            'offline_until' => $refreshed['offline_until'],
        ], 'Token offline berhasil diperbarui');
    }
}
