<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiClientMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key diperlukan',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 401);
        }

        $client = ApiClient::query()->where('api_key', $apiKey)->active()->first();

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 401);
        }

        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');
        $nonce = $request->header('X-Nonce');

        if (! $timestamp || ! $signature) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp dan signature diperlukan',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 401);
        }

        $requestTime = strtotime($timestamp);

        if (! $requestTime || abs(now()->timestamp - $requestTime) > 300) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp tidak valid atau telah kedaluwarsa',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 401);
        }

        if ($nonce) {
            $nonceKey = "api_nonce:{$client->id}:{$nonce}";
            if (Cache::has($nonceKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nonce telah digunakan (replay terdeteksi)',
                    'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
                ], 401);
            }
            Cache::put($nonceKey, true, 300);
        }

        $payload = $request->getMethod()."\n".$request->path()."\n".$timestamp;

        if ($nonce) {
            $payload .= "\n".$nonce;
        }

        $payload .= "\n".$request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $client->api_secret, true));

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Signature tidak valid',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 401);
        }

        $client->touchLastUsed();

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
