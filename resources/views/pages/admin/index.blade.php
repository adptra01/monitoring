<?php

use App\Models\License;
use App\Models\Device;
use App\Models\ActivationRequest;
use App\Models\Product;
use Livewire\Volt\Component;

new class extends Component
{
    public int $totalProducts = 0;
    public int $totalLicenses = 0;
    public int $activeLicenses = 0;
    public int $pendingActivations = 0;
    public int $totalDevices = 0;

    public function mount(): void
    {
        $this->totalProducts = Product::count();
        $this->totalLicenses = License::count();
        $this->activeLicenses = License::where('status', 'active')->count();
        $this->pendingActivations = ActivationRequest::where('status', 'pending')->count();
        $this->totalDevices = Device::count();
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">Overview of your licensing system</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Products</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $this->totalProducts }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Total Licenses</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $this->totalLicenses }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Active Licenses</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ $this->activeLicenses }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Pending Activations</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ $this->pendingActivations }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="text-sm font-medium text-gray-500">Total Devices</div>
        <div class="text-3xl font-bold text-gray-900 mt-2">{{ $this->totalDevices }}</div>
    </div>
</div>