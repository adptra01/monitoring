<?php

namespace Tests\Feature;

use App\Enums\LicenseMode;
use App\Enums\LicenseStatus;
use App\Models\ApiClient;
use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private function signRequest(string $method, string $path, string $body, ApiClient $client): array
    {
        $timestamp = now()->toIso8601String();
        $nonce = Str::random(32);
        $signPath = ltrim($path, '/');
        $payload = "{$method}\n{$signPath}\n{$timestamp}\n{$nonce}\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $client->api_secret, true));

        return [
            'X-API-Key' => $client->api_key,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
        ];
    }

    public function test_complete_license_api_flow(): void
    {
        $client = ApiClient::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $license = License::factory()->create([
            'product_id' => $product->id,
            'subscription_plan_id' => null,
            'status' => LicenseStatus::Active,
            'mode' => LicenseMode::Offline,
            'max_devices' => 3,
            'expires_at' => now()->addYear(),
        ]);

        $deviceFingerprint = 'device-'.Str::random(32);

        // 1. Activate device
        $body = json_encode([
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => $deviceFingerprint,
                'name' => 'Test Device',
                'platform' => 'Linux',
                'platform_version' => '6.0',
            ],
        ]);
        $headers = $this->signRequest('POST', '/api/v1/activate', $body, $client);
        $response = $this->postJson('/api/v1/activate', [
            'license_key' => $license->key,
            'device' => [
                'fingerprint' => $deviceFingerprint,
                'name' => 'Test Device',
                'platform' => 'Linux',
                'platform_version' => '6.0',
            ],
        ], $headers);

        $response->assertStatus(200);
        $response->assertJsonPath('data.offline_until', fn ($v) => $v !== null);

        // 2. Validate license with device
        $body = json_encode([
            'license_key' => $license->key,
            'device' => ['fingerprint' => $deviceFingerprint],
        ]);
        $headers = $this->signRequest('POST', '/api/v1/validate', $body, $client);
        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $license->key,
            'device' => ['fingerprint' => $deviceFingerprint],
        ], $headers);

        $response->assertStatus(200);
        $response->assertJsonPath('data.valid', true);
        $response->assertJsonPath('data.product', $product->name);
        $response->assertJsonPath('data.offline_until', fn ($v) => $v !== null);

        // 3. Check status endpoint
        $headers = $this->signRequest('GET', "/api/v1/status/{$license->key}/{$deviceFingerprint}", '', $client);
        $response = $this->call('GET', "/api/v1/status/{$license->key}/{$deviceFingerprint}", [], [], [], $this->transformHeadersToServerVars($headers));
        $response->assertStatus(200);
        $response->assertJsonPath('data.license_valid', true);

        // 4. Deactivate device
        $body = json_encode([
            'license_key' => $license->key,
            'device' => ['fingerprint' => $deviceFingerprint],
        ]);
        $headers = $this->signRequest('POST', '/api/v1/deactivate', $body, $client);
        $response = $this->postJson('/api/v1/deactivate', [
            'license_key' => $license->key,
            'device' => ['fingerprint' => $deviceFingerprint],
        ], $headers);

        $response->assertStatus(200);

        // 5. Verify deactivation
        $device = Device::where('fingerprint', $deviceFingerprint)->first();
        $this->assertNotNull($device);
        $this->assertFalse($device->is_active);
    }
}
