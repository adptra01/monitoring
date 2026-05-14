<?php

use App\Enums\LicenseStatus;
use App\Models\License;
use function Livewire\Volt\{state};

state([
    'customer_name' => '',
    'customer_email' => '',
    'max_devices' => 1,
    'notes' => '',
    'show_suspend' => false,
    'show_revoke' => false,
    'show_reset' => false,
]);

$lic = License::with('product', 'devices')->findOrFail((int) $license);

$customer_name = $lic->customer_name;
$customer_email = $lic->customer_email;
$max_devices = $lic->max_devices;
$notes = $lic->notes ?? '';

$update = function () use ($lic) {
    $this->validate([
        'customer_name' => 'required|max:255',
        'customer_email' => 'required|email|max:255',
        'max_devices' => 'required|integer|min:1|max:100',
        'notes' => 'nullable|string',
    ]);

    $lic->update([
        'customer_name' => $this->customer_name,
        'customer_email' => $this->customer_email,
        'max_devices' => $this->max_devices,
        'notes' => $this->notes ?: null,
    ]);

    session()->flash('success', 'License updated.');
};

$toggleStatus = function (string $newStatus) use ($lic) {
    if ($newStatus === 'active') {
        $lic->update(['status' => LicenseStatus::Active]);
        session()->flash('success', 'License activated.');
    } elseif ($newStatus === 'suspended') {
        $lic->update(['status' => LicenseStatus::Suspended]);
        session()->flash('success', 'License suspended.');
    } elseif ($newStatus === 'revoked') {
        $lic->update(['status' => LicenseStatus::Revoked]);
        $lic->devices()->update(['is_active' => false]);
        session()->flash('success', 'License revoked permanently.');
    }

    $this->show_suspend = false;
    $this->show_revoke = false;
};

$forceReset = function () use ($lic) {
    $lic->devices()->update(['is_active' => false]);
    $lic->activationRequests()->where('status', 'pending')->update(['status' => 'rejected']);
    $lic->update(['activated_at' => null]);
    session()->flash('success', 'All devices reset. Customer can reactivate.');
    $this->show_reset = false;
};
?>

<x-layouts.admin>
    <x-slot:header>
        License: <span class="font-mono text-sm">{{ $lic->license_key }}</span>
    </x-slot:header>

    <div class="mb-6 flex gap-2 flex-wrap">
        <span @class([
            'px-3 py-1 rounded text-sm font-medium',
            'bg-green-100 text-green-800' => $lic->status === 'active',
            'bg-yellow-100 text-yellow-800' => $lic->status === 'suspended',
            'bg-red-100 text-red-800' => $lic->status === 'revoked',
            'bg-gray-100 text-gray-800' => $lic->status === 'expired',
        ])>
            Status: {{ $lic->status }}
        </span>
        <span class="px-3 py-1 rounded text-sm bg-blue-100 text-blue-800">
            Key: {{ $lic->license_key }}
        </span>
        <span class="px-3 py-1 rounded text-sm bg-purple-100 text-purple-800">
            Product: {{ $lic->product->name }}
        </span>
        <span class="px-3 py-1 rounded text-sm bg-gray-100 text-gray-800">
            Devices: {{ $lic->activeDeviceCount() }}/{{ $lic->max_devices }}
        </span>
        <span @class([
            'px-3 py-1 rounded text-sm',
            'bg-red-50 text-red-700' => $lic->expired_at->isPast(),
            'bg-gray-100 text-gray-800' => !$lic->expired_at->isPast(),
        ])>
            Expires: {{ $lic->expired_at->format('d M Y') }}
        </span>
    </div>

    <form wire:submit="update" class="max-w-lg space-y-4 mb-8">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="customer_name" value="Customer Name" />
                <x-text-input wire:model="customer_name" id="customer_name" class="w-full mt-1" />
                <x-input-error for="customer_name" class="mt-1" />
            </div>
            <div>
                <x-input-label for="customer_email" value="Customer Email" />
                <x-text-input wire:model="customer_email" id="customer_email" type="email" class="w-full mt-1" />
                <x-input-error for="customer_email" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="max_devices" value="Max Devices" />
            <x-text-input wire:model="max_devices" id="max_devices" type="number" min="1" class="w-full mt-1" />
            <x-input-error for="max_devices" class="mt-1" />
        </div>

        <div>
            <x-input-label for="notes" value="Notes" />
            <textarea wire:model="notes" id="notes" rows="2"
                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <x-input-error for="notes" class="mt-1" />
        </div>

        <div class="flex gap-4">
            <x-primary-button>Update</x-primary-button>
            <a href="/admin/licenses">
                <x-secondary-button type="button">Back</x-secondary-button>
            </a>
        </div>
    </form>

    <hr class="my-6">

    <div class="space-y-4">
        <h3 class="text-lg font-semibold">Actions</h3>

        <div class="flex gap-3 flex-wrap">
            @if($lic->status === 'suspended')
                <x-primary-button wire:click="toggleStatus('active')">Activate License</x-primary-button>
            @elseif($lic->status === 'active')
                <button wire:click="$set('show_suspend', true)"
                    class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-400">
                    Suspend
                </button>
            @endif

            @if(in_array($lic->status, ['active', 'suspended']))
                <button wire:click="$set('show_revoke', true)"
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                    Revoke
                </button>
            @endif

            <button wire:click="$set('show_reset', true)"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500">
                Force Reset Devices
            </button>
        </div>

        @if($show_suspend)
            <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                <p class="text-sm text-yellow-800 mb-3">Suspend license <strong>{{ $lic->license_key }}</strong>?</p>
                <div class="flex gap-2">
                    <button wire:click="toggleStatus('suspended')" class="px-3 py-1 bg-yellow-500 text-white rounded text-sm">Yes, Suspend</button>
                    <button wire:click="$set('show_suspend', false)" class="px-3 py-1 bg-gray-200 rounded text-sm">Cancel</button>
                </div>
            </div>
        @endif

        @if($show_revoke)
            <div class="bg-red-50 border border-red-200 rounded p-4">
                <p class="text-sm text-red-800 mb-1 font-semibold">PERMANENT ACTION</p>
                <p class="text-sm text-red-700 mb-3">Revoke license <strong>{{ $lic->license_key }}</strong>? This cannot be undone.</p>
                <div class="flex gap-2">
                    <button wire:click="toggleStatus('revoked')" class="px-3 py-1 bg-red-600 text-white rounded text-sm">Yes, Revoke Permanently</button>
                    <button wire:click="$set('show_revoke', false)" class="px-3 py-1 bg-gray-200 rounded text-sm">Cancel</button>
                </div>
            </div>
        @endif

        @if($show_reset)
            <div class="bg-gray-50 border border-gray-200 rounded p-4">
                <p class="text-sm text-gray-800 mb-3">
                    Deactivate ALL <strong>{{ $lic->activeDeviceCount() }}</strong> device(s)?
                    Customer will need to reactivate from scratch.
                </p>
                <div class="flex gap-2">
                    <button wire:click="forceReset" class="px-3 py-1 bg-gray-600 text-white rounded text-sm">Yes, Reset All Devices</button>
                    <button wire:click="$set('show_reset', false)" class="px-3 py-1 bg-gray-200 rounded text-sm">Cancel</button>
                </div>
            </div>
        @endif
    </div>

    <hr class="my-6">

    <div>
        <h3 class="text-lg font-semibold mb-4">Active Devices</h3>
        @if($lic->devices->where('is_active', true)->isEmpty())
            <p class="text-gray-500">No active devices.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="pb-2 pr-4">Device Name</th>
                            <th class="pb-2 pr-4">Device ID</th>
                            <th class="pb-2 pr-4">IP Address</th>
                            <th class="pb-2 pr-4">Activated</th>
                            <th class="pb-2">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lic->devices->where('is_active', true) as $device)
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-4">{{ $device->device_name }}</td>
                                <td class="py-2 pr-4 font-mono text-xs">{{ $device->device_id }}</td>
                                <td class="py-2 pr-4">{{ $device->ip_address ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $device->activated_at->format('d M Y H:i') }}</td>
                                <td class="py-2">{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
