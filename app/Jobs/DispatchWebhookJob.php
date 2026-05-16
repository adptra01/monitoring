<?php

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public WebhookEndpoint $endpoint,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $jsonPayload = json_encode($this->payload);
        $timestamp = now()->toIso8601String();
        $signature = hash_hmac('sha256', $timestamp.'.'.$jsonPayload, $this->endpoint->secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
            ])
            ->post($this->endpoint->url, $this->payload);

        if ($response->failed()) {
            Log::warning('Webhook dispatch failed', [
                'webhook_endpoint_id' => $this->endpoint->id,
                'url' => $this->endpoint->url,
                'event' => $this->payload['event'] ?? 'unknown',
                'status' => $response->status(),
                'attempt' => $this->attempts(),
            ]);

            $this->release($this->backoff * $this->attempts());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook dispatch exception after retries', [
            'webhook_endpoint_id' => $this->endpoint->id,
            'url' => $this->endpoint->url,
            'event' => $this->payload['event'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
