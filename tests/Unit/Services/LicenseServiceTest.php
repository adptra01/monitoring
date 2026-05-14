<?php

namespace Tests\Unit\Services;

use App\Enums\ActivationRequestStatus;
use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseKeyService;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private LicenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LicenseService(new LicenseKeyService());
    }

    public function test_create_generates_key_if_not_provided(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $license = $this->service->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
            'mode' => LicenseMode::Online,
            'max_devices' => 3,
        ]);

        $this->assertNotEmpty($license->key);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license->key);
    }

    public function test_validate_returns_valid_for_active_license(): void
    {
        $license = License::factory()->create([
            'status' => LicenseStatus::Active,
            'expires_at' => now()->addMonth(),
        ]);

        $result = $this->service->validate($license);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['reason']);
    }

    public function test_validate_returns_invalid_for_suspended_license(): void
    {
        $license = License::factory()->create([
            'status' => LicenseStatus::Suspended,
        ]);

        $result = $this->service->validate($license);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('suspended', $result['reason']);
    }

    public function test_validate_returns_invalid_for_expired_license(): void
    {
        $license = License::factory()->create([
            'status' => LicenseStatus::Active,
            'expires_at' => now()->subDay(),
        ]);

        $result = $this->service->validate($license);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('expired', $result['reason']);
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

    public function test_suspend_changes_license_status(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Active]);

        $this->service->suspend($license);
        $license->refresh();

        $this->assertEquals(LicenseStatus::Suspended, $license->status);
    }

    public function test_revoke_changes_license_status(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Active]);

        $this->service->revoke($license);
        $license->refresh();

        $this->assertEquals(LicenseStatus::Revoked, $license->status);
    }

    public function test_restore_changes_license_status_back_to_active(): void
    {
        $license = License::factory()->create(['status' => LicenseStatus::Suspended]);

        $this->service->restore($license);
        $license->refresh();

        $this->assertEquals(LicenseStatus::Active, $license->status);
    }

    public function test_approve_activation_request_updates_status(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);
        $request = ActivationRequest::factory()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'status' => ActivationRequestStatus::Pending,
        ]);

        $this->service->approveActivationRequest($request, auth()->id());

        $request->refresh();
        $this->assertEquals(ActivationRequestStatus::Approved, $request->status);
        $this->assertNotNull($request->activated_at);
    }

    public function test_reject_activation_request_updates_status(): void
    {
        $license = License::factory()->create();
        $device = Device::factory()->create(['license_id' => $license->id]);
        $request = ActivationRequest::factory()->create([
            'license_id' => $license->id,
            'device_id' => $device->id,
            'status' => ActivationRequestStatus::Pending,
        ]);

        $this->service->rejectActivationRequest($request, 'Device not authorized', auth()->id());

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
}