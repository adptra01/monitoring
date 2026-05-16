<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BruteForceMiddleware
{
    protected int $maxAttempts = 5;

    protected int $lockoutMinutes = 15;

    public function handle(Request $request, Closure $next): Response
    {
        $licenseKey = $request->input('license_key');

        if (! $licenseKey) {
            return $next($request);
        }

        $cacheKey = 'brute_force:'.md5($licenseKey);
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= $this->maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam '.$this->lockoutMinutes.' menit.',
                'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
            ], 429);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 403 || $response->getStatusCode() === 401) {
            Cache::put($cacheKey, $attempts + 1, now()->addMinutes($this->lockoutMinutes));
        }

        if ($response->getStatusCode() === 200) {
            Cache::forget($cacheKey);
        }

        return $response;
    }
}
