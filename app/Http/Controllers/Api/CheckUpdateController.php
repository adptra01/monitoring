<?php

namespace App\Http\Controllers\Api;

use App\Enums\LicenseStatus;
use App\Http\Requests\CheckUpdateRequest;
use App\Models\License;
use Illuminate\Http\JsonResponse;

class CheckUpdateController extends ApiController
{
    public function __invoke(CheckUpdateRequest $request): JsonResponse
    {
        $licenseKey = $request->validated('license_key');
        $currentVersion = $request->validated('current_version');

        $license = License::where('key', $licenseKey)->first();

        if (! $license || $license->status !== LicenseStatus::Active) {
            return $this->error('License not valid for update check', 403);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return $this->error('License has expired', 403);
        }

        // Logic for update can be expanded here. For now, returning no update.
        return $this->success([
            'update_available' => false,
            'latest_version' => $currentVersion,
            'download_url' => null,
            'message' => 'You are using the latest version',
            'release_notes' => null,
        ]);
    }
}
