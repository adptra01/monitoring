<?php

namespace Tests\Concerns;

use App\Models\ApiClient;
use Illuminate\Testing\TestResponse;

trait SignsApiRequests
{
    protected ApiClient $apiClient;

    public function setUpHmac(): void
    {
        $this->apiClient = ApiClient::factory()->create([
            'is_active' => true,
            'rate_limit' => 1000,
        ]);
    }

    protected function apiHeaders(): array
    {
        $timestamp = now()->toIso8601String();

        return [
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => '',
        ];
    }

    protected function signedJson(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $timestamp = now()->toIso8601String();
        $body = $method === 'GET' ? '' : json_encode($data);
        $payload = $method."\n".ltrim($uri, '/')."\n".$timestamp."\n".$body;
        $signature = base64_encode(hash_hmac('sha256', $payload, $this->apiClient->api_secret, true));

        $allHeaders = array_merge([
            'X-API-Key' => $this->apiClient->api_key,
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
        ], $headers);

        if ($method === 'GET') {
            return $this->call('GET', $uri, [], [], [], $this->transformHeadersToServerVars($allHeaders));
        }

        return $this->postJson($uri, $data, $allHeaders);
    }

    protected function signedGet(string $uri, array $headers = []): TestResponse
    {
        return $this->signedJson('GET', $uri, [], $headers);
    }
}
