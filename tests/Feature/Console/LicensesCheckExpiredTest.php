<?php

namespace Tests\Feature\Console;

use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicensesCheckExpiredTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_expired_licenses_are_marked_expired(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')
            ->expectsOutputToContain('expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('licenses', [
            'status' => 'expired',
        ]);
    }

    #[Test]
    public function test_active_licenses_are_not_marked_expired(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->addMonth(),
        ]);

        $this->artisan('licenses:check-expired')
            ->assertSuccessful();

        $this->assertDatabaseMissing('licenses', [
            'status' => 'expired',
        ]);
    }

    #[Test]
    public function test_multiple_expired_licenses(): void
    {
        LicenseFactory::new()->active()->count(3)->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')
            ->expectsOutput('Marked 3 license(s) as expired.')
            ->assertSuccessful();
    }

    #[Test]
    public function test_already_expired_licenses_are_not_double_counted(): void
    {
        LicenseFactory::new()->expired()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')
            ->expectsOutput('Marked 0 license(s) as expired.')
            ->assertSuccessful();
    }

    #[Test]
    public function test_creates_audit_log_when_licenses_expire(): void
    {
        LicenseFactory::new()->active()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'license.expired',
        ]);
    }
}
