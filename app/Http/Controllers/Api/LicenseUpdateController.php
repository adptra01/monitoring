<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckUpdateRequest;
use App\Services\LicenseService;

class LicenseUpdateController extends Controller
{
    public function __construct(
        private LicenseService $licenseService,
    ) {}

    public function __invoke(CheckUpdateRequest $request): array
    {
        return $this->licenseService->checkUpdate(
            licenseKey: $request->input('license_key'),
            currentVersion: $request->input('current_version'),
        );
    }
}
