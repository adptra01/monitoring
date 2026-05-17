<?php

namespace App\Observers;

use App\Jobs\SyncLicenseToGithubJob;
use App\Models\License;
use Illuminate\Support\Facades\Log;

class LicenseObserver
{
    public function created(License $license): void
    {
        try {
            dispatch(new SyncLicenseToGithubJob($license));
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch GitHub sync job', [
                'license' => $license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(License $license): void
    {
        if ($license->wasChanged(['status', 'expires_at', 'max_devices'])) {
            try {
                dispatch(new SyncLicenseToGithubJob($license));
            } catch (\Throwable $e) {
                Log::warning('Failed to dispatch GitHub sync job', [
                    'license' => $license->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
