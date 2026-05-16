<?php

namespace App\Services;

use App\Models\ApiClient;
use App\Models\Device;
use App\Models\License;
use Illuminate\Support\Facades\Cache;

class MetricsService
{
    public function collect(): array
    {
        return Cache::remember('metrics:data', 60, function () {
            return [
                'licenses_total' => License::count(),
                'licenses_active' => License::where('status', 'active')->count(),
                'licenses_expired' => License::where('status', 'expired')->count(),
                'licenses_suspended' => License::where('status', 'suspended')->count(),
                'devices_total' => Device::count(),
                'devices_active' => Device::where('is_active', true)->count(),
                'api_clients_total' => ApiClient::count(),
                'api_clients_active' => ApiClient::where('is_active', true)->count(),
            ];
        });
    }

    public function toPrometheusString(): string
    {
        $metrics = $this->collect();
        $lines = [];

        $lines[] = '# HELP license_monitor_licenses_total Total number of licenses';
        $lines[] = '# TYPE license_monitor_licenses_total gauge';
        $lines[] = 'license_monitor_licenses_total '.$metrics['licenses_total'];

        $lines[] = '# HELP license_monitor_licenses_active Number of active licenses';
        $lines[] = '# TYPE license_monitor_licenses_active gauge';
        $lines[] = 'license_monitor_licenses_active '.$metrics['licenses_active'];

        $lines[] = '# HELP license_monitor_licenses_expired Number of expired licenses';
        $lines[] = '# TYPE license_monitor_licenses_expired gauge';
        $lines[] = 'license_monitor_licenses_expired '.$metrics['licenses_expired'];

        $lines[] = '# HELP license_monitor_licenses_suspended Number of suspended licenses';
        $lines[] = '# TYPE license_monitor_licenses_suspended gauge';
        $lines[] = 'license_monitor_licenses_suspended '.$metrics['licenses_suspended'];

        $lines[] = '# HELP license_monitor_devices_total Total number of registered devices';
        $lines[] = '# TYPE license_monitor_devices_total gauge';
        $lines[] = 'license_monitor_devices_total '.$metrics['devices_total'];

        $lines[] = '# HELP license_monitor_devices_active Number of active devices';
        $lines[] = '# TYPE license_monitor_devices_active gauge';
        $lines[] = 'license_monitor_devices_active '.$metrics['devices_active'];

        $lines[] = '# HELP license_monitor_api_clients_total Total number of API clients';
        $lines[] = '# TYPE license_monitor_api_clients_total gauge';
        $lines[] = 'license_monitor_api_clients_total '.$metrics['api_clients_total'];

        $lines[] = '# HELP license_monitor_api_clients_active Number of active API clients';
        $lines[] = '# TYPE license_monitor_api_clients_active gauge';
        $lines[] = 'license_monitor_api_clients_active '.$metrics['api_clients_active'];

        return implode("\n", $lines)."\n";
    }
}
