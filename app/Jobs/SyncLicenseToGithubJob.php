<?php

namespace App\Jobs;

use App\Models\License;
use App\Services\GithubLicenseSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncLicenseToGithubJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public License $license,
    ) {}

    public function uniqueId(): string
    {
        return 'github-sync-'.$this->license->id;
    }

    public function handle(GithubLicenseSyncService $syncService): void
    {
        $syncService->sync($this->license);
    }
}
