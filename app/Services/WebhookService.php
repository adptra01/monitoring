<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\WebhookEndpoint;

class WebhookService
{
    public function dispatch(string $event, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('is_active', true)->get();

        foreach ($endpoints as $endpoint) {
            if ($endpoint->hasEvent($event)) {
                DispatchWebhookJob::dispatch($endpoint, $payload);
            }
        }
    }
}
