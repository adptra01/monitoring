<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\LicenseStatus;
use App\Models\AuditLog;
use App\Models\License;
use Illuminate\Console\Command;

class LicensesCheckExpired extends Command
{
    protected $signature = 'licenses:check-expired';

    protected $description = 'Mark expired licenses as expired';

    public function handle(): int
    {
        $count = License::query()
            ->where('status', LicenseStatus::Active->value)
            ->whereDate('expired_at', '<', now())
            ->update(['status' => LicenseStatus::Expired->value]);

        $this->info("Marked {$count} license(s) as expired.");

        if ($count > 0) {
            AuditLog::log(
                action: AuditAction::LicenseExpired->value,
                payload: ['count' => $count],
            );
        }

        return Command::SUCCESS;
    }
}
