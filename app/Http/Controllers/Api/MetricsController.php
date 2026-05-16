<?php

namespace App\Http\Controllers\Api;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends ApiController
{
    public function __construct(
        protected MetricsService $metricsService,
    ) {}

    public function prometheus(Request $request): JsonResponse
    {
        $format = $request->query('format', 'json');

        if ($format === 'prometheus') {
            return response()->json([
                'success' => true,
                'data' => [
                    'prometheus' => $this->metricsService->toPrometheusString(),
                ],
                'meta' => $this->meta(),
            ]);
        }

        return $this->success($this->metricsService->collect());
    }
}
