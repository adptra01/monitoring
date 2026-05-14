<?php

namespace Tests\Feature\Services;

use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use Database\Factories\DeviceFactory;
use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private LicenseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LicenseService(new LicenseKeyService);
    }

    #[Test]
    public function it_validates_active_license_with_bound_device(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertTrue($result['valid']);
        $this->assertEquals('active', $result['status']);
        $this->assertArrayHasKey('cache_until', $result);
        $this->assertArrayHasKey('cache_ttl_seconds', $result);
    }

    #[Test]
    public function it_rejects_expired_license(): void
    {
        $license = LicenseFactory::new()->expired()->create();
        $device = DeviceFactory::new()->create(['license_id' => $license->id]);

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertFalse($result['valid']);
        $this->assertEquals('expired', $result['status']);
    }

    #[Test]
    public function it_rejects_revoked_license(): void
    {
        $license = LicenseFactory::new()->revoked()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertFalse($result['valid']);
        $this->assertEquals('revoked', $result['status']);
    }

    #[Test]
    public function it_rejects_suspended_license(): void
    {
        $license = LicenseFactory::new()->suspended()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->validate($license->license_key, $device->device_id);

        $this->assertFalse($result['valid']);
        $this->assertEquals('suspended', $result['status']);
    }

    #[Test]
    public function it_rejects_device_mismatch(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $result = $this->service->validate($license->license_key, 'unknown-device');

        $this->assertFalse($result['valid']);
        $this->assertEquals('device_mismatch', $result['status']);
    }

    #[Test]
    public function it_rejects_nonexistent_license(): void
    {
        $result = $this->service->validate('LIC-AAAAAAAA-BBBBBBBB', 'some-device');

        $this->assertFalse($result['valid']);
        $this->assertEquals('not_found', $result['status']);
    }

    #[Test]
    public function it_updates_last_seen_on_validation(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $this->travel(1)->hour();

        $this->service->validate($license->license_key, $device->device_id);

        $device->refresh();
        $this->assertTrue($device->last_seen_at->greaterThan($device->activated_at));
    }

    #[Test]
    public function it_activates_first_device(): void
    {
        $license = LicenseFactory::new()->active()->create();

        $result = $this->service->activate(
            $license->license_key,
            'new-device-uuid',
            'Kasir Utama'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['status']);
        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function it_returns_idempotent_for_same_device(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();

        $result = $this->service->activate(
            $license->license_key,
            $device->device_id,
            $device->device_name,
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Device already activated', $result['message']);
        $this->assertEquals(1, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function it_activates_multiple_devices_up_to_max(): void
    {
        $license = LicenseFactory::new()->active()->withMaxDevices(3)->create();

        for ($i = 0; $i < 3; $i++) {
            $result = $this->service->activate(
                $license->license_key,
                "device-{$i}",
                "Device {$i}",
            );

            $this->assertTrue($result['success']);
        }

        $this->assertEquals(3, $license->fresh()->activeDeviceCount());
    }

    #[Test]
    public function it_creates_pending_request_when_device_limit_reached(): void
    {
        $license = LicenseFactory::new()->active()->withMaxDevices(1)->withDevices(1)->create();

        $result = $this->service->activate(
            $license->license_key,
            'new-device-uuid',
            'New Device',
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('pending_approval', $result['status']);
        $this->assertArrayHasKey('activation_request_id', $result);

        $this->assertDatabaseHas('activation_requests', [
            'license_id' => $license->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_rejects_activation_for_expired_license(): void
    {
        $license = LicenseFactory::new()->expired()->create();

        $result = $this->service->activate(
            $license->license_key,
            'new-device',
            'Test Device',
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('expired', $result['status']);
    }

    #[Test]
    public function it_rejects_activation_for_nonexistent_key(): void
    {
        $result = $this->service->activate(
            'LIC-INVALID-KEY',
            'new-device',
            'Test',
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('not_found', $result['status']);
    }

    #[Test]
    public function it_reactivates_deactivated_device(): void
    {
        $license = LicenseFactory::new()->active()->withDevices(1)->create();
        $device = $license->devices->first();
        $device->update(['is_active' => false]);

        $result = $this->service->activate(
            $license->license_key,
            $device->device_id,
            $device->device_name,
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Device reactivated', $result['message']);
    }
}
