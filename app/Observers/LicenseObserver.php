<?php

namespace App\Observers;

use App\Jobs\SyncLicenseToGithubJob;
use App\Models\License;

class LicenseObserver
{
    public function created(License $license): void
    {
        dispatch(new SyncLicenseToGithubJob($license));
    }

    public function updated(License $license): void
    {
        if ($license->wasChanged(['status', 'expires_at', 'max_devices'])) {
            dispatch(new SyncLicenseToGithubJob($license));
        }
    }
}
