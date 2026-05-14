<?php

namespace App\Console\Commands;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseStatus;
use App\Models\ActivationRequest;
use App\Models\License;
use Illuminate\Console\Command;

class LicensesCheckExpired extends Command
{
    protected $signature = 'licenses:check-expired';

    protected $description = 'Check for expired licenses and update their status';

    public function handle(): int
    {
        $expiredCount = 0;
        $requestExpiredCount = 0;

        License::where('status', LicenseStatus::Active)
            ->where('expires_at', '<=', now())
            ->chunk(100, function ($licenses) use (&$expiredCount) {
                foreach ($licenses as $license) {
                    $license->update(['status' => LicenseStatus::Expired]);
                    $this->info("License {$license->key} marked as expired.");
                    $expiredCount++;
                }
            });

        ActivationRequest::where('status', ActivationRequestStatus::Pending)
            ->where('expires_at', '<=', now())
            ->chunk(100, function ($requests) use (&$requestExpiredCount) {
                foreach ($requests as $request) {
                    $request->update(['status' => ActivationRequestStatus::Expired]);
                    $this->info("Activation request #{$request->id} marked as expired.");
                    $requestExpiredCount++;
                }
            });

        $this->info("Processed {$expiredCount} expired licenses and {$requestExpiredCount} expired activation requests.");

        return Command::SUCCESS;
    }
}