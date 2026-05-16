<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->attributes->get('api_client');

        if (! $client instanceof ApiClient) {
            return $next($request);
        }

        $allowedIps = $client->allowed_ips;

        if (empty($allowedIps)) {
            return $next($request);
        }

        $requestIp = $request->ip();

        foreach ($allowedIps as $ip) {
            if ($this->ipMatches($requestIp, $ip)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'IP address tidak diizinkan',
            'meta' => ['timestamp' => now()->toIso8601String(), 'api_version' => 'v1'],
        ], 403);
    }

    protected function ipMatches(string $requestIp, string $allowedIp): bool
    {
        if (str_contains($allowedIp, '/')) {
            return $this->ipInCidr($requestIp, $allowedIp);
        }

        return $requestIp === $allowedIp;
    }

    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        return ($ip & $mask) === ($subnet & $mask);
    }
}
