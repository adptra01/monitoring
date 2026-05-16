<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (int) ((microtime(true) - $start) * 1000);

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            $client = $request->attributes->get('api_client');

            Log::channel('json')->info('API Request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'api_client_id' => $client?->id,
                'api_client_name' => $client?->name,
            ]);
        }

        return $response;
    }
}
