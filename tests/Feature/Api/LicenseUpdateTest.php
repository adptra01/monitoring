<?php

namespace Tests\Feature\Api;

use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicenseUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_returns_no_update_for_active_license(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/check-update', [
            'license_key' => $license->license_key,
            'current_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson([
            'update_available' => false,
            'latest_version' => '1.0.0',
        ]);
    }

    #[Test]
    public function test_returns_no_update_for_expired_license(): void
    {
        $license = LicenseFactory::new()->expired()->create();

        $response = $this->postJson('/api/license/check-update', [
            'license_key' => $license->license_key,
            'current_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson([
            'update_available' => false,
            'message' => 'Unable to check updates',
        ]);
    }

    #[Test]
    public function test_returns_422_with_invalid_key_format(): void
    {
        $response = $this->postJson('/api/license/check-update', [
            'license_key' => 'BAD-KEY',
            'current_version' => '1.0.0',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_returns_422_when_version_missing(): void
    {
        $response = $this->postJson('/api/license/check-update', [
            'license_key' => 'LIC-A1B2C3D4-E5F6G7H8',
        ]);

        $response->assertStatus(422);
    }
}
