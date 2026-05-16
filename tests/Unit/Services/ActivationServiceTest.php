<?php

namespace Tests\Unit\Services;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseMode;
use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use App\Services\ActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivationService;
    }

    public function test_create_request_returns_null_for_offline_mode(): void
    {
        $license = License::factory()->offline()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);

        $result = $this->service->createRequest($device);

        $this->assertNull($result);
    }

    public function test_create_request_creates_pending_request(): void
    {
        $license = License::factory()->create(['mode' => LicenseMode::Online]);
        $device = Device::factory()->create(['license_id' => $license->id]);

        $request = $this->service->createRequest($device);

        $this->assertNotNull($request);
        $this->assertEquals(ActivationRequestStatus::Pending, $request->status);
        $this->assertEquals($license->id, $request->license_id);
        $this->assertEquals($device->id, $request->device_id);
    }

    public function test_create_request_returns_existing_pending_request(): void
    {
        $license = License::factory()->create(['mode' => LicenseMode::Online]);
        $device = Device::factory()->create(['license_id' => $license->id]);
        $existing = ActivationRequest::factory()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'status' => ActivationRequestStatus::Pending,
            'expires_at' => now()->addMinutes(30),
        ]);

        $request = $this->service->createRequest($device);

        $this->assertEquals($existing->id, $request->id);
    }

    public function test_approve_request_updates_status(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);
        $request = ActivationRequest::factory()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'status' => ActivationRequestStatus::Pending,
        ]);

        $this->service->approveRequest($request);

        $request->refresh();
        $this->assertEquals(ActivationRequestStatus::Approved, $request->status);
        $this->assertNotNull($request->activated_at);
    }

    public function test_reject_request_updates_status(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);
        $request = ActivationRequest::factory()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'status' => ActivationRequestStatus::Pending,
        ]);

        $this->service->rejectRequest($request, 'Device not authorized');

        $request->refresh();
        $this->assertEquals(ActivationRequestStatus::Rejected, $request->status);
        $this->assertEquals('Device not authorized', $request->rejection_reason);
    }

    public function test_verify_activation_returns_valid_for_approved_request(): void
    {
        $license = License::factory()->create(['mode' => LicenseMode::Online]);
        $device = Device::factory()->create([
            'license_id' => $license->id,
            'last_seen_at' => now(),
        ]);
        $request = ActivationRequest::factory()->approved()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
        ]);

        $result = $this->service->verifyActivation($device, $request->code);

        $this->assertTrue($result['valid']);
    }

    public function test_verify_activation_returns_invalid_for_unapproved_device(): void
    {
        $license = License::factory()->create(['mode' => LicenseMode::Online]);
        $device = Device::factory()->create([
            'license_id' => $license->id,
            'last_seen_at' => now()->subDays(10),
        ]);

        $result = $this->service->verifyActivation($device, '');

        $this->assertFalse($result['valid']);
    }

    public function test_is_within_grace_period_returns_true_for_recent_activity(): void
    {
        $device = Device::factory()->create(['last_seen_at' => now()->subDay()]);

        $this->assertTrue($this->service->isWithinGracePeriod($device));
    }

    public function test_is_within_grace_period_returns_false_for_old_activity(): void
    {
        $device = Device::factory()->create(['last_seen_at' => now()->subDays(30)]);

        $this->assertFalse($this->service->isWithinGracePeriod($device));
    }

    public function test_calculate_offline_until_returns_date(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create([
            'license_id' => $license->id,
            'last_seen_at' => now()->subDay(),
        ]);

        $offlineUntil = $this->service->calculateOfflineUntil($device);

        $this->assertNotNull($offlineUntil);
        $this->assertTrue($offlineUntil->isFuture());
    }

    public function test_calculate_offline_until_returns_null_for_stale_device(): void
    {
        $device = Device::factory()->create(['last_seen_at' => now()->subDays(30)]);

        $offlineUntil = $this->service->calculateOfflineUntil($device);

        $this->assertNull($offlineUntil);
    }
}
