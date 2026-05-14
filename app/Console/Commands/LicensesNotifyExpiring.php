<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LicensesNotifyExpiring extends Command
{
    protected $signature = 'licenses:notify-expiring {--days=7 : Days before expiration to notify}';

    protected $description = 'Notify users about licenses expiring soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $notifyCount = 0;

        License::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->chunk(100, function ($licenses) use (&$notifyCount) {
                foreach ($licenses as $license) {
                    $daysUntil = now()->diffInDays($license->expires_at);

                    Log::info("License {$license->key} expires in {$daysUntil} days", [
                        'license_id' => $license->id,
                        'user_id' => $license->user_id,
                        'expires_at' => $license->expires_at->toIso8601String(),
                    ]);

                    $this->info("License {$license->key} expiring in {$daysUntil} days.");
                    $notifyCount++;
                }
            });

        $this->info("Notified about {$notifyCount} licenses expiring within {$days} days.");

        return Command::SUCCESS;
    }
}