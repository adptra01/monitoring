<?php

namespace Tests\Unit\Services;

use App\Models\Device;
use App\Models\License;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeviceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeviceService;
    }

    public function test_register_creates_device(): void
    {
        $license = License::factory()->create(['max_devices' => 3]);

        $device = $this->service->register($license, [
            'name' => 'Test Device',
            'fingerprint' => str_repeat('a', 64),
            'platform' => 'windows',
        ]);

        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals($license->id, $device->license_id);
        $this->assertEquals('Test Device', $device->name);
    }

    public function test_register_generates_fingerprint_if_not_provided(): void
    {
        $license = License::factory()->create(['max_devices' => 3]);

        $device = $this->service->register($license, ['name' => 'Test Device']);

        $this->assertNotEmpty($device->fingerprint);
        $this->assertEquals(64, strlen($device->fingerprint));
    }

    public function test_check_device_limit_returns_true_when_under_limit(): void
    {
        $license = License::factory()->create(['max_devices' => 3]);
        Device::factory()->create(['license_id' => $license->id]);

        $this->assertTrue($this->service->checkDeviceLimit($license));
    }

    public function test_check_device_limit_returns_false_when_at_limit(): void
    {
        $license = License::factory()->create(['max_devices' => 2]);
        Device::factory()->count(2)->create(['license_id' => $license->id]);

        $this->assertFalse($this->service->checkDeviceLimit($license));
    }

    public function test_find_by_fingerprint_returns_device(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);

        $found = $this->service->findByFingerprint($license, $device->fingerprint);

        $this->assertNotNull($found);
        $this->assertEquals($device->id, $found->id);
    }

    public function test_find_by_fingerprint_returns_null_for_mismatch(): void
    {
        $license = License::factory()->create();

        $found = $this->service->findByFingerprint($license, 'nonexistent-fingerprint');

        $this->assertNull($found);
    }

    public function test_touch_updates_last_seen_at(): void
    {
        $device = Device::factory()->create(['last_seen_at' => now()->subDays(10)]);

        $this->service->touch($device);
        $device->refresh();

        $this->assertTrue($device->last_seen_at->isAfter(now()->subMinute()));
    }
}
