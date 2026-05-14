<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ValidateLicenseRequest;
use App\Services\LicenseService;

class LicenseValidationController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(ValidateLicenseRequest $request): array
    {
        return $this->licenseService->validate(
            licenseKey: $request->input('license_key'),
            deviceId: $request->input('device_id'),
            appVersion: $request->input('app_version'),
        );
    }
}
