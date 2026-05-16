<?php

namespace Tests\Feature\Api;

use App\DTOs\OfflineTokenData;
use App\Models\Device;
use App\Models\License;
use App\Models\LicenseToken;
use App\Models\Product;
use App\Services\OfflineTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\SignsApiRequests;
use Tests\TestCase;

class OfflineTokenTest extends TestCase
{
    use RefreshDatabase;
    use SignsApiRequests;

    protected OfflineTokenService $tokenService;

    protected License $license;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHmac();

        $this->tokenService = app(OfflineTokenService::class);

        $product = Product::factory()->create();
        $this->license = License::factory()->create([
            'product_id' => $product->id,
            'status' => 'active',
        ]);
        $this->device = Device::factory()->create([
            'license_id' => $this->license->id,
            'fingerprint' => str_repeat('a', 64),
        ]);
    }

    public function test_issue_creates_token_and_stores_in_db(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('offline_until', $result);
        $this->assertNotEmpty($result['token']);

        $tokenHash = hash('sha256', $result['token']);
        $this->assertDatabaseHas('license_tokens', [
            'token_hash' => $tokenHash,
            'license_id' => $this->license->id,
            'device_id' => $this->device->id,
        ]);
    }

    public function test_verify_accepts_valid_token(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);
        $verification = $this->tokenService->verify($result['token'], $this->device->fingerprint);

        $this->assertTrue($verification['valid']);
        $this->assertInstanceOf(OfflineTokenData::class, $verification['data']);
        $this->assertEquals($this->license->key, $verification['data']->licenseKey);
    }

    public function test_verify_rejects_wrong_fingerprint(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);
        $verification = $this->tokenService->verify($result['token'], str_repeat('b', 64));

        $this->assertFalse($verification['valid']);
        $this->assertEquals('Token tidak cocok dengan perangkat', $verification['reason']);
    }

    public function test_verify_rejects_tampered_token(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);
        $tampered = $result['token'].'tampered';
        $verification = $this->tokenService->verify($tampered, $this->device->fingerprint);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('Token tidak valid', $verification['reason']);
    }

    public function test_verify_rejects_expired_token(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);

        $tokenHash = hash('sha256', $result['token']);
        LicenseToken::where('token_hash', $tokenHash)->update([
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $decrypted = Crypt::decryptString($result['token']);
        $data = json_decode($decrypted, true);
        $data['offline_until'] = Carbon::now()->subDay()->toIso8601String();
        $data['server_time'] = Carbon::now()->toIso8601String();
        $modifiedToken = Crypt::encryptString(json_encode($data));

        $verification = $this->tokenService->verify($modifiedToken, $this->device->fingerprint);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('Token offline telah kedaluwarsa', $verification['reason']);
    }

    public function test_verify_detects_clock_drift(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);

        Carbon::setTestNow(Carbon::now()->addHours(5));

        $verification = $this->tokenService->verify($result['token'], $this->device->fingerprint);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('Deteksi manipulasi waktu', $verification['reason']);

        Carbon::setTestNow();
    }

    public function test_verify_rejects_revoked_token(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);

        $tokenHash = hash('sha256', $result['token']);
        LicenseToken::where('token_hash', $tokenHash)->update(['revoked_at' => Carbon::now()]);

        $verification = $this->tokenService->verify($result['token'], $this->device->fingerprint);

        $this->assertFalse($verification['valid']);
        $this->assertEquals('Token telah dicabut', $verification['reason']);
    }

    public function test_refresh_issues_new_token_and_revokes_old(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);
        $oldTokenHash = hash('sha256', $result['token']);

        $refreshed = $this->tokenService->refresh($this->license, $this->device, $result['token']);

        $this->assertArrayHasKey('token', $refreshed);
        $this->assertNotEquals($result['token'], $refreshed['token']);

        $storedToken = LicenseToken::where('token_hash', $oldTokenHash)->first();
        $this->assertNotNull($storedToken->revoked_at);
    }

    public function test_refresh_rejects_revoked_token(): void
    {
        $result = $this->tokenService->issue($this->license, $this->device);
        $this->tokenService->refresh($this->license, $this->device, $result['token']);

        $secondRefresh = $this->tokenService->refresh($this->license, $this->device, $result['token']);

        $this->assertFalse($secondRefresh['valid']);
        $this->assertEquals('Token telah dicabut', $secondRefresh['reason']);
    }

    public function test_revoke_by_license_revokes_all_device_tokens(): void
    {
        $token1 = $this->tokenService->issue($this->license, $this->device);
        $device2 = Device::factory()->create([
            'license_id' => $this->license->id,
            'fingerprint' => str_repeat('c', 64),
        ]);
        $token2 = $this->tokenService->issue($this->license, $device2);

        $this->tokenService->revokeByLicense($this->license);

        $this->assertFalse($this->tokenService->verify($token1['token'], $this->device->fingerprint)['valid']);
        $this->assertFalse($this->tokenService->verify($token2['token'], $device2->fingerprint)['valid']);
    }

    public function test_revoke_by_device_revokes_only_that_device_tokens(): void
    {
        $device2 = Device::factory()->create([
            'license_id' => $this->license->id,
            'fingerprint' => str_repeat('d', 64),
        ]);

        $token1 = $this->tokenService->issue($this->license, $this->device);
        $token2 = $this->tokenService->issue($this->license, $device2);

        $this->tokenService->revokeByDevice($this->device);

        $this->assertFalse($this->tokenService->verify($token1['token'], $this->device->fingerprint)['valid']);
        $this->assertTrue($this->tokenService->verify($token2['token'], $device2->fingerprint)['valid']);
    }

    public function test_validate_endpoint_returns_offline_token(): void
    {
        $body = json_encode([
            'license_key' => $this->license->key,
            'device' => [
                'fingerprint' => $this->device->fingerprint,
            ],
        ]);
        $timestamp = now()->toIso8601String();
        $payload = "POST\napi/v1/validate\n{$timestamp}\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $response = $this->postJson('/api/v1/validate', [
            'license_key' => $this->license->key,
            'device' => ['fingerprint' => $this->device->fingerprint],
        ], [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['offline_token', 'cache_until'],
            'meta',
        ]);

        $offlineToken = $response->json('data.offline_token');
        $this->assertNotEmpty($offlineToken);

        $verification = $this->tokenService->verify($offlineToken, $this->device->fingerprint);
        $this->assertTrue($verification['valid']);
    }

    public function test_token_refresh_endpoint_returns_new_token(): void
    {
        $issueResult = $this->tokenService->issue($this->license, $this->device);

        $body = json_encode([
            'license_key' => $this->license->key,
            'device' => ['fingerprint' => $this->device->fingerprint],
            'offline_token' => $issueResult['token'],
        ]);
        $timestamp = now()->toIso8601String();
        $payload = "POST\napi/v1/token/refresh\n{$timestamp}\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $response = $this->postJson('/api/v1/token/refresh', [
            'license_key' => $this->license->key,
            'device' => ['fingerprint' => $this->device->fingerprint],
            'offline_token' => $issueResult['token'],
        ], [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['offline_token', 'offline_until'],
            'meta',
        ]);

        $newToken = $response->json('data.offline_token');
        $this->assertNotEquals($issueResult['token'], $newToken);

        $verification = $this->tokenService->verify($newToken, $this->device->fingerprint);
        $this->assertTrue($verification['valid']);
    }

    public function test_token_refresh_rejects_invalid_token(): void
    {
        $body = json_encode([
            'license_key' => $this->license->key,
            'device' => ['fingerprint' => $this->device->fingerprint],
            'offline_token' => 'invalid-token-data',
        ]);
        $timestamp = now()->toIso8601String();
        $payload = "POST\napi/v1/token/refresh\n{$timestamp}\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $response = $this->postJson('/api/v1/token/refresh', [
            'license_key' => $this->license->key,
            'device' => ['fingerprint' => $this->device->fingerprint],
            'offline_token' => 'invalid-token-data',
        ], [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(403);
    }
}
