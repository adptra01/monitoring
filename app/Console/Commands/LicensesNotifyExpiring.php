<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class LicensesNotifyExpiring extends Command
{
    protected $signature = 'licenses:notify-expiring';

    protected $description = 'List licenses expiring within the next 7 days';

    public function handle(): int
    {
        $licenses = License::query()
            ->where('status', 'active')
            ->whereDate('expired_at', '>', now())
            ->whereDate('expired_at', '<=', now()->addDays(7))
            ->get();

        if ($licenses->isEmpty()) {
            $this->info('No licenses expiring within 7 days.');

            return Command::SUCCESS;
        }

        $this->table(
            ['License Key', 'Customer', 'Email', 'Expires At'],
            $licenses->map(fn (License $l) => [
                $l->license_key,
                $l->customer_name,
                $l->customer_email,
                $l->expired_at->format('Y-m-d'),
            ])->toArray(),
        );

        $this->info("Found {$licenses->count()} license(s) expiring within 7 days.");

        return Command::SUCCESS;
    }
}
