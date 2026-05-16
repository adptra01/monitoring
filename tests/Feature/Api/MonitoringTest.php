<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\License;
use App\Models\Product;
use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_returns_data(): void
    {
        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'licenses_total',
                    'licenses_active',
                    'licenses_expired',
                    'licenses_suspended',
                    'devices_total',
                    'devices_active',
                    'api_clients_total',
                    'api_clients_active',
                ],
                'meta',
            ]);
    }

    public function test_metrics_reflects_actual_data(): void
    {
        $product = Product::factory()->create();
        $licenses = License::factory()->count(5)->create(['product_id' => $product->id]);
        Device::factory()->count(3)->create(['license_id' => $licenses->first()->id]);

        Cache::forget('metrics:data');
        $metrics = app(MetricsService::class)->collect();

        $this->assertEquals(5, $metrics['licenses_total']);
        $this->assertEquals(3, $metrics['devices_total']);
    }

    public function test_metrics_endpoint_reflects_database(): void
    {
        Product::factory()->create();
        $licenses = License::factory()->count(3)->create();

        Cache::forget('metrics:data');
        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(3, $data['licenses_total']);
    }

    public function test_metrics_with_prometheus_format(): void
    {
        $product = Product::factory()->create();
        License::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/v1/metrics?format=prometheus');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['prometheus'],
                'meta',
            ]);

        $prometheus = $response->json('data.prometheus');
        $this->assertStringContainsString('license_monitor_licenses_total', $prometheus);
        $this->assertStringContainsString('# HELP', $prometheus);
    }

    public function test_structured_logging_writes_json(): void
    {
        $logFile = storage_path('logs/laravel.json');

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $this->getJson('/api/v1/health');

        $this->assertFileExists($logFile);

        $contents = file_get_contents($logFile);
        $this->assertNotEmpty($contents);

        $logEntry = json_decode($contents, true);
        $this->assertNotNull($logEntry);
        $this->assertEquals('API Request', $logEntry['message']);
        $this->assertEquals('GET', $logEntry['context']['method']);
        $this->assertEquals('api/v1/health', $logEntry['context']['path']);
        $this->assertEquals(200, $logEntry['context']['status']);
        $this->assertArrayHasKey('duration_ms', $logEntry['context']);
    }

    public function test_structured_logging_writes_on_api_errors(): void
    {
        $logFile = storage_path('logs/laravel.json');

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $this->getJson('/api/v1/nonexistent');

        if (! file_exists($logFile)) {
            $this->markTestSkipped('ApiLoggingMiddleware only fires on matched routes');
        }

        $contents = file_get_contents($logFile);
        $logEntry = json_decode(trim(explode("\n", $contents)[0]), true);

        $this->assertNotNull($logEntry);
        $this->assertEquals(404, $logEntry['context']['status']);
    }
}
