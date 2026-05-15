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
            return $this->error('Lisensi tidak valid untuk pemeriksaan pembaruan', 403);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return $this->error('Lisensi telah kedaluwarsa', 403);
        }

        return $this->success([
            'update_available' => false,
            'latest_version' => $currentVersion,
            'download_url' => null,
            'message' => 'Anda menggunakan versi terbaru',
            'release_notes' => null,
        ]);
    }
}
