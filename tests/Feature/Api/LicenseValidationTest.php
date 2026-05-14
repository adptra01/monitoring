<?php

namespace Tests\Feature\Api;

use App\Enums\LicenseStatus;
use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_returns_error_for_invalid_license_key(): void
    {
        $response = $this->postJson('/api/v1/validate', [
            'license_key' => 'XXXX-XXXX-XXXX-XXXX',
            'device' => [
                'fingerprint' => str_repeat('a', 64),
            ],
        ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Invalid license key']);
    }

    public function test_validate_returns_success_for_valid_license(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
            'max_devices' => 3,
            'expires_at' => now()->addMonth(),
        ]);
        $device = Device::factory()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => $device->fingerprint,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'license_key' => '****-****-****-****',
                ],
            ]);
    }

    public function test_validate_returns_error_for_unregistered_device(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
        ]);

        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => str_repeat('b', 64),
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Device not registered']);
    }

    public function test_validate_returns_error_for_suspended_license(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Suspended,
        ]);
        $device = Device::factory()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => $device->fingerprint,
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_validate_returns_error_for_expired_license(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
        $device = Device::factory()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => $device->fingerprint,
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_validate_requires_valid_fingerprint_format(): void
    {
        $response = $this->postJson('/api/v1/validate', [
            'license_key' => 'XXXX-XXXX-XXXX-XXXX',
            'device' => [
                'fingerprint' => 'too-short',
            ],
        ]);

        $response->assertStatus(422);
    }
}