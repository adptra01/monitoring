<?php

namespace Tests\Feature\Api;

use App\Enums\LicenseStatus;
use App\Models\ActivationRequest;
use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\LicenseKeyService;
use Database\Factories\LicenseFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicenseEndToEndTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_complete_license_lifecycle(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        // 1. ADMIN CREATES PRODUCT
        $product = Product::create([
            'name' => 'POS Application',
            'slug' => 'pos-application',
            'description' => 'Point of Sale System',
            'is_active' => true,
        ]);
        $this->assertModelExists($product);

        // 2. ADMIN CREATES PLAN
        $plan = SubscriptionPlan::create([
            'product_id' => $product->id,
            'name' => 'Monthly',
            'duration_days' => 30,
            'price' => 100000,
            'is_active' => true,
        ]);
        $this->assertModelExists($plan);

        // 3. ADMIN CREATES LICENSE
        $keyService = new LicenseKeyService;
        $licenseKey = $keyService->generate();
        $this->assertMatchesRegularExpression('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $licenseKey);

        $license = License::create([
            'product_id' => $product->id,
            'customer_name' => 'Toko Makmur',
            'customer_email' => 'toko@makmur.com',
            'license_key' => $licenseKey,
            'status' => LicenseStatus::Active,
            'max_devices' => 2,
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $license->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertDatabaseHas('licenses', [
            'license_key' => $licenseKey,
            'status' => 'active',
        ]);

        // 4. CLIENT ACTIVATES FIRST DEVICE
        $deviceId1 = (string) Str::uuid();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId1,
            'device_name' => 'Kasir Utama',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'active',
            'message' => 'Device activated successfully',
        ]);

        $this->assertEquals(1, $license->fresh()->activeDeviceCount());

        // 5. CLIENT VALIDATES FIRST DEVICE
        $response = $this->postJson('/api/license/validate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId1,
            'app_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'status' => 'active',
        ]);
        $response->assertJsonStructure([
            'valid', 'status', 'expired_at', 'cache_until',
            'cache_ttl_seconds', 'server_time', 'message',
        ]);

        // 6. CLIENT ACTIVATES SECOND DEVICE (within max_devices)
        $deviceId2 = (string) Str::uuid();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId2,
            'device_name' => 'Kasir Cadangan',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'active',
        ]);

        $this->assertEquals(2, $license->fresh()->activeDeviceCount());

        // 7. CLIENT ACTIVATES THIRD DEVICE (exceeds max_devices → pending approval)
        $deviceId3 = (string) Str::uuid();

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId3,
            'device_name' => 'Device Baru',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'status' => 'pending_approval',
        ]);

        $requestId = $response->json('activation_request_id');
        $this->assertNotNull($requestId);

        $this->assertDatabaseHas('activation_requests', [
            'id' => $requestId,
            'status' => 'pending',
        ]);

        // 8. ADMIN APPROVES ACTIVATION REQUEST
        $activationRequest = ActivationRequest::findOrFail($requestId);
        $activationRequest->approve($admin->id);

        $this->assertDatabaseHas('activation_requests', [
            'id' => $requestId,
            'status' => 'approved',
        ]);

        // 9. CLIENT ACTIVATES ON NEW DEVICE (now should work)
        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId3,
            'device_name' => 'Device Baru',
        ]);

        $response->assertOk();
        $this->assertContains($response->json('message'), [
            'Device activated successfully',
            'Device already activated',
        ]);

        $this->assertEquals(2, $license->fresh()->activeDeviceCount());

        // 10. ADMIN SUSPENDS LICENSE
        $license->update(['status' => LicenseStatus::Suspended]);

        // 11. CLIENT VALIDATION FAILS (suspended)
        $response = $this->postJson('/api/license/validate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'suspended',
        ]);

        // 12. ADMIN REVOKES LICENSE
        $license->update(['status' => LicenseStatus::Revoked]);

        // 13. CLIENT VALIDATION FAILS (revoked)
        $response = $this->postJson('/api/license/validate', [
            'license_key' => $licenseKey,
            'device_id' => $deviceId1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => false,
            'status' => 'revoked',
        ]);

        // 14. SCHEDULER MARKS EXPIRED — test with a different license
        $expiredLicense = LicenseFactory::new()->active()->create([
            'expired_at' => now()->subDay(),
        ]);

        $this->artisan('licenses:check-expired')
            ->expectsOutputToContain('expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('licenses', [
            'id' => $expiredLicense->id,
            'status' => 'expired',
        ]);
    }
}
