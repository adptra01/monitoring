<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DeactivateDeviceRequest;
use App\Models\License;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;

class DeactivateController extends ApiController
{
    public function __construct(
        protected DeviceService $deviceService,
    ) {}

    public function __invoke(DeactivateDeviceRequest $request): JsonResponse
    {
        $license = License::where('key', $request->validated('license_key'))->first();

        if (! $license) {
            return $this->error('Kunci lisensi tidak valid', 404);
        }

        $fingerprint = $request->validated('device.fingerprint');
        $device = $this->deviceService->findByFingerprint($license, $fingerprint);

        if (! $device) {
            return $this->error('Perangkat tidak terdaftar', 404);
        }

        $this->deviceService->deactivate($device);

        return $this->success(null, 'Perangkat berhasil dinonaktifkan');
    }
}
