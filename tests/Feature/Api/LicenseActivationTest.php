<?php

namespace Tests\Feature\Api;

use App\Enums\LicenseStatus;
use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_returns_error_for_invalid_license_key(): void
    {
        $response = $this->postJson('/api/v1/activate', [
            'license_key' => 'XXXX-XXXX-XXXX-XXXX',
            'device' => [
                'fingerprint' => str_repeat('a', 64),
                'name' => 'Test Device',
            ],
        ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Kunci lisensi tidak valid']);
    }

    public function test_activate_returns_error_for_expired_license(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => str_repeat('b', 64),
                'name' => 'Test Device',
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_activate_registers_new_device(): void
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

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => str_repeat('c', 64),
                'name' => 'Test Device',
                'platform' => 'windows',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('devices', [
            'license_id' => $license->id,
            'fingerprint' => str_repeat('c', 64),
        ]);
    }

    public function test_activate_returns_pending_status_when_device_limit_reached(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
            'max_devices' => 1,
        ]);
        Device::factory()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => str_repeat('d', 64),
                'name' => 'New Device',
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['data']['requires_approval'] ?? false);
        $this->assertArrayHasKey('activation_code', $data['data']);
    }

    public function test_activate_returns_pending_status_for_approval_mode(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
            'max_devices' => 1,
        ]);
        Device::factory()->create(['license_id' => $license->id]);

        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => str_repeat('e', 64),
                'name' => 'Pending Device',
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['data']['requires_approval'] ?? false);
        $this->assertArrayHasKey('activation_code', $data['data']);
    }

    public function test_status_returns_device_info(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => LicenseStatus::Active,
        ]);
        $device = Device::factory()->create([
            'license_id' => $license->id,
            'last_seen_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/status/{$license->key}/{$device->fingerprint}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_status_returns_error_for_unknown_device(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $license = License::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/v1/status/{$license->key}/".str_repeat('x', 64));

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Perangkat tidak terdaftar']);
    }
}
