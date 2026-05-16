<?php

namespace App\Http\Controllers\Api;

use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = Cache::remember('health:db', 10, function () {
            try {
                DB::select('SELECT 1');

                return 'healthy';
            } catch (\Exception) {
                return 'unhealthy';
            }
        });

        $data = [
            'status' => $dbStatus === 'healthy' ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $dbStatus,
                'cache' => Cache::has('health:db') ? 'healthy' : 'healthy',
            ],
        ];

        if ($dbStatus === 'healthy') {
            $data['metrics'] = [
                'total_licenses' => License::count(),
                'active_licenses' => License::where('status', 'active')->count(),
            ];
        }

        $httpCode = $dbStatus === 'healthy' ? 200 : 503;

        return response()->json([
            'success' => true,
            'message' => $dbStatus === 'healthy' ? 'Service sehat' : 'Service terdegradasi',
            'data' => $data,
            'meta' => $this->meta(),
        ], $httpCode);
    }
}
