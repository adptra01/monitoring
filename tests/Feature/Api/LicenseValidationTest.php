<?php

namespace Tests\Feature\Api;

use Database\Factories\DeviceFactory;
use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicenseValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_can_validate_active_license_with_bound_device(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'status' => 'active',
        ]);
        $response->assertJsonStructure([
            'valid', 'status', 'expired_at',
            'cache_until', 'cache_ttl_seconds',
            'server_time', 'message',
        ]);
    }

    #[Test]
    public function test_cannot_validate_expired_license(): void
    {
        $license = LicenseFactory::new()->expired()->create();
        $device = DeviceFactory::new()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'expired',
        ]);
    }

    #[Test]
    public function test_cannot_validate_revoked_license(): void
    {
        $license = LicenseFactory::new()->revoked()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'revoked',
        ]);
    }

    #[Test]
    public function test_validation_fails_on_device_mismatch(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $license->license_key,
            'device_id' => 'unregistered-device-id',
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'device_mismatch',
        ]);
    }

    #[Test]
    public function test_cannot_validate_with_invalid_key_format(): void
    {
        $response = $this->postJson('/api/license/validate', [
            'license_key' => 'INVALID-KEY',
            'device_id' => 'some-device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['license_key']);
    }

    #[Test]
    public function test_validation_returns_422_when_license_key_missing(): void
    {
        $response = $this->postJson('/api/license/validate', [
            'device_id' => 'some-device',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_validation_returns_422_when_device_id_missing(): void
    {
        $response = $this->postJson('/api/license/validate', [
            'license_key' => 'LIC-A1B2C3D4-E5F6G7H8',
        ]);

        $response->assertStatus(422);
    }
}
