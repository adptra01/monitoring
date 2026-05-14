<?php

use App\Models\License;
use App\Services\LicenseService;
use Livewire\Volt\Component;

new class extends Component
{
    public ?License $license = null;

    public function mount(string $license): void
    {
        $this->license = License::where('key', $license)->orWhere('id', $license)->firstOrFail();
    }

    public function suspend(): void
    {
        app(LicenseService::class)->suspend($this->license);
        $this->license->refresh();
        session()->flash('message', 'License suspended.');
    }

    public function revoke(): void
    {
        app(LicenseService::class)->revoke($this->license);
        $this->license->refresh();
        session()->flash('message', 'License revoked.');
    }

    public function restore(): void
    {
        app(LicenseService::class)->restore($this->license);
        $this->license->refresh();
        session()->flash('message', 'License restored.');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">License Details</h1>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">License Information</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Key</dt>
                    <dd class="font-medium"><code>{{ $license->key }}</code></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs rounded @if($license->status->value === 'active') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $license->status->label() }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Mode</dt>
                    <dd>{{ $license->mode->label() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Max Devices</dt>
                    <dd>{{ $license->max_devices }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Expires</dt>
                    <dd>{{ $license->expires_at?->format('Y-m-d') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Product & User</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Product</dt>
                    <dd>{{ $license->product->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">User</dt>
                    <dd>{{ $license->user->name }} ({{ $license->user->email }})</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Devices</dt>
                    <dd>{{ $license->devices->count() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-semibold mb-4">Actions</h2>
        <div class="flex gap-4">
            @if ($license->status->value !== 'active')
                <button wire:click="restore" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Restore License
                </button>
            @endif
            @if ($license->status->value === 'active')
                <button wire:click="suspend" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    Suspend License
                </button>
                <button wire:click="revoke" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Revoke License
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-lg font-semibold mb-4">Devices</h2>
        @if ($license->devices->isEmpty())
            <p class="text-gray-500">No devices registered.</p>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Fingerprint</th>
                        <th class="px-4 py-2 text-left">Platform</th>
                        <th class="px-4 py-2 text-left">Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($license->devices as $device)
                        <tr>
                            <td class="px-4 py-2">{{ $device->name }}</td>
                            <td class="px-4 py-2"><code class="text-xs">{{ $device->fingerprint }}</code></td>
                            <td class="px-4 py-2">{{ $device->platform }}</td>
                            <td class="px-4 py-2">{{ $device->last_seen_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>