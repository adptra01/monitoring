<?php

use App\Models\ActivationRequest;
use App\Models\Device;
use App\Models\License;
use Carbon\Carbon;

$stats = [
    'active_licenses' => License::where('status', 'active')->count(),
    'expired_today' => License::whereDate('expired_at', today())->count(),
    'pending_activations' => ActivationRequest::where('status', 'pending')->count(),
    'active_devices' => Device::where('is_active', true)->count(),
];

$recentRequests = ActivationRequest::with('license')
    ->where('status', 'pending')
    ->latest()
    ->take(5)
    ->get();

$expiringSoon = License::query()
    ->where('status', 'active')
    ->whereBetween('expired_at', [now(), now()->addDays(7)])
    ->take(5)
    ->get();
?>

<x-layouts.admin>
    <x-slot:header>Dashboard</x-slot:header>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="text-blue-600 text-2xl font-bold">{{ $stats['active_licenses'] }}</div>
            <div class="text-blue-800 text-sm font-medium">Active Licenses</div>
        </div>
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <div class="text-red-600 text-2xl font-bold">{{ $stats['expired_today'] }}</div>
            <div class="text-red-800 text-sm font-medium">Expired Today</div>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <div class="text-yellow-600 text-2xl font-bold">{{ $stats['pending_activations'] }}</div>
            <div class="text-yellow-800 text-sm font-medium">Pending Activations</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="text-green-600 text-2xl font-bold">{{ $stats['active_devices'] }}</div>
            <div class="text-green-800 text-sm font-medium">Active Devices</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold mb-4">Pending Activation Requests</h3>
            @if($recentRequests->isEmpty())
                <p class="text-gray-500">No pending requests</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="pb-2">License</th>
                                <th class="pb-2">New Device</th>
                                <th class="pb-2">Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequests as $req)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $req->license->license_key }}</td>
                                    <td class="py-2">{{ $req->new_device_name }}</td>
                                    <td class="py-2">{{ $req->requested_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="/admin/activation-requests" class="text-blue-600 text-sm hover:underline mt-2 inline-block">
                    View all →
                </a>
            @endif
        </div>

        <div>
            <h3 class="text-lg font-semibold mb-4">Expiring Soon (7 days)</h3>
            @if($expiringSoon->isEmpty())
                <p class="text-gray-500">No licenses expiring soon</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="pb-2">License</th>
                                <th class="pb-2">Customer</th>
                                <th class="pb-2">Expires</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringSoon as $lic)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $lic->license_key }}</td>
                                    <td class="py-2">{{ $lic->customer_name }}</td>
                                    <td class="py-2 text-red-600">{{ $lic->expired_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
