<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivateLicenseRequest;
use App\Services\LicenseService;

class LicenseActivationController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(ActivateLicenseRequest $request): array
    {
        return $this->licenseService->activate(
            licenseKey: $request->input('license_key'),
            deviceId: $request->input('device_id'),
            deviceName: $request->input('device_name'),
        );
    }
}
