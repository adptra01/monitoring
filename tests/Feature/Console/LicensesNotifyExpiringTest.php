<?php

namespace Tests\Feature\Console;

use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicensesNotifyExpiringTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_shows_expiring_licenses(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->addDays(3),
        ]);

        $this->artisan('licenses:notify-expiring')
            ->expectsOutputToContain('expiring')
            ->assertSuccessful();
    }

    #[Test]
    public function test_shows_no_expiring_licenses_message(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->addMonth(),
        ]);

        $this->artisan('licenses:notify-expiring')
            ->expectsOutput('No licenses expiring within 7 days.')
            ->assertSuccessful();
    }

    #[Test]
    public function test_does_not_include_expired_licenses(): void
    {
        LicenseFactory::new()->expired()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:notify-expiring')
            ->expectsOutput('No licenses expiring within 7 days.')
            ->assertSuccessful();
    }

    #[Test]
    public function test_includes_licenses_expiring_exactly_7_days(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->addDays(7),
        ]);

        $this->artisan('licenses:notify-expiring')
            ->expectsOutputToContain('expiring')
            ->assertSuccessful();
    }
}
