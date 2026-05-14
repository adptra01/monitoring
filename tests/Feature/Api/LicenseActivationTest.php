<?php

namespace Tests\Feature\Api;

use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_can_activate_first_device(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Kasir Utama',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'active',
        ]);

        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function test_activation_is_idempotent_for_same_device(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Device already activated',
        ]);

        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function test_can_activate_multiple_devices_up_to_max(): void
    {
        $license = LicenseFactory::new()->active()->withMaxDevices(3)->create();

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/license/activate', [
                'license_key' => $license->license_key,
                'device_id' => (string) Str::uuid(),
                'device_name' => "Device {$i}",
            ]);

            $response->assertOk();
            $response->assertJsonPath('success', true);
        }

        $this->assertEquals(3, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function test_device_limit_creates_pending_request(): void
    {
        $license = LicenseFactory::new()->active()->withMaxDevices(1)->withDevices(1)->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'New Device',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('activation_requests', [
            'license_id' => $license->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function test_cannot_activate_expired_license(): void
    {
        $license = LicenseFactory::new()->expired()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'expired',
        ]);
    }

    #[Test]
    public function test_cannot_activate_with_invalid_key(): void
    {
        $response = $this->postJson('/api/license/activate', [
            'license_key' => 'INVALID',
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_cannot_activate_revoked_license(): void
    {
        $license = LicenseFactory::new()->revoked()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
            'device_name' => 'Test Device',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'revoked',
        ]);
    }

    #[Test]
    public function test_returns_422_when_device_name_missing(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $license->license_key,
            'device_id' => (string) Str::uuid(),
        ]);

        $response->assertStatus(422);
    }
}
