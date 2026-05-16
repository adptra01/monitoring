<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsApiRequests;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;
    use SignsApiRequests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHmac();
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_replay_attack_is_rejected(): void
    {
        $timestamp = now()->toIso8601String();
        $nonce = 'unique-nonce-123';
        $body = json_encode(['license_key' => 'test']);
        $payload = "POST\napi/v1/validate\n{$timestamp}\n{$nonce}\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $headers = [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/validate', ['license_key' => 'test'], $headers)
            ->assertStatus(422);

        $this->postJson('/api/v1/validate', ['license_key' => 'test'], $headers)
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Nonce telah digunakan (replay terdeteksi)']);
    }

    public function test_replay_attack_is_rejected_for_get_requests(): void
    {
        $timestamp = now()->toIso8601String();
        $nonce = 'unique-nonce-456';
        $key = 'FAKE-KEY';
        $fingerprint = str_repeat('f', 64);
        $payload = "GET\napi/v1/status/{$key}/{$fingerprint}\n{$timestamp}\n{$nonce}\n";
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $headers = [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        $uri = "/api/v1/status/{$key}/{$fingerprint}";
        $this->call('GET', $uri, [], [], [], $this->transformHeadersToServerVars($headers))
            ->assertStatus(404);

        $this->call('GET', $uri, [], [], [], $this->transformHeadersToServerVars($headers))
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Nonce telah digunakan (replay terdeteksi)']);
    }

    public function test_api_key_rotation(): void
    {
        $oldSecret = $this->apiClient->api_secret;

        $this->apiClient->regenerateSecret();
        $this->apiClient->refresh();

        $this->assertNotEquals($oldSecret, $this->apiClient->api_secret);
        $this->assertStringStartsWith('lcs_', $this->apiClient->api_secret);
        $this->assertEquals(68, strlen($this->apiClient->api_secret));
    }

    public function test_old_secret_is_rejected_after_rotation(): void
    {
        $oldSecret = $this->apiClient->api_secret;

        $this->apiClient->regenerateSecret();
        $this->apiClient->refresh();

        $timestamp = now()->toIso8601String();
        $body = json_encode(['license_key' => 'test']);
        $payload = "POST\n/api/v1/validate\n{$timestamp}\n\n{$body}";
        $signature = base64_encode(hash_hmac('sha256', $payload, $oldSecret, true));

        $headers = [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/validate', ['license_key' => 'test'], $headers)
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Signature tidak valid']);
    }

    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'timestamp',
                    'services' => ['database', 'cache'],
                ],
                'meta',
            ]);
    }

    public function test_auth_endpoint_validates_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth', [
            'api_key' => $this->apiClient->api_key,
            'api_secret' => $this->apiClient->api_secret,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'token_type', 'expires_at'],
                'meta',
            ]);
    }

    public function test_auth_endpoint_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth', [
            'api_key' => $this->apiClient->api_key,
            'api_secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Kredensial tidak valid']);
    }

    public function test_auth_endpoint_rejects_missing_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth', []);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'API key dan secret diperlukan']);
    }

    public function test_ip_whitelist_blocks_unauthorized_ip(): void
    {
        $this->apiClient->update(['allowed_ips' => ['192.168.1.1']]);

        $response = $this->signedGet('/api/v1/status/FAKE-KEY/'.str_repeat('f', 64));

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'IP address tidak diizinkan']);
    }

    public function test_ip_whitelist_allows_authorized_ip(): void
    {
        $this->apiClient->update(['allowed_ips' => ['127.0.0.1']]);

        $response = $this->signedGet('/api/v1/status/FAKE-KEY/fake-fingerprint');

        $response->assertStatus(404);
    }

    public function test_ip_whitelist_allows_all_when_empty(): void
    {
        $this->apiClient->update(['allowed_ips' => null]);

        $response = $this->signedGet('/api/v1/status/FAKE-KEY/fake-fingerprint');

        $response->assertStatus(404);
    }
}
