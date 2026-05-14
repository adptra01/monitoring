<?php

use App\Models\License;
use function Livewire\Volt\{state};

state(['search' => '', 'statusFilter' => '']);

$licenses = fn() => License::with('product')
    ->when($this->search, fn($q) => $q->where(function($q) {
        $q->where('license_key', 'like', '%'.$this->search.'%')
          ->orWhere('customer_name', 'like', '%'.$this->search.'%')
          ->orWhere('customer_email', 'like', '%'.$this->search.'%');
    }))
    ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
    ->latest()
    ->paginate(20);
?>

<x-layouts.admin>
    <x-slot:header>Licenses</x-slot:header>

    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
        <div class="flex gap-3">
            <x-text-input wire:model="search" placeholder="Search license key, name, email..." class="w-72" />
            <select wire:model="statusFilter"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="revoked">Revoked</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        <a href="/admin/licenses/create">
            <x-primary-button type="button">Create License</x-primary-button>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="pb-3 pr-3">License Key</th>
                    <th class="pb-3 pr-3">Customer</th>
                    <th class="pb-3 pr-3">Product</th>
                    <th class="pb-3 pr-3">Status</th>
                    <th class="pb-3 pr-3">Devices</th>
                    <th class="pb-3 pr-3">Expires</th>
                    <th class="pb-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($licenses as $lic)
                    <tr class="border-b last:border-0">
                        <td class="py-3 pr-3 font-mono text-xs">{{ $lic->license_key }}</td>
                        <td class="py-3 pr-3">
                            <div>{{ $lic->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $lic->customer_email }}</div>
                        </td>
                        <td class="py-3 pr-3">{{ $lic->product->name }}</td>
                        <td class="py-3 pr-3">
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-medium',
                                'bg-green-100 text-green-800' => $lic->status === 'active',
                                'bg-yellow-100 text-yellow-800' => $lic->status === 'suspended',
                                'bg-red-100 text-red-800' => $lic->status === 'revoked',
                                'bg-gray-100 text-gray-800' => $lic->status === 'expired',
                            ])>{{ $lic->status }}</span>
                        </td>
                        <td class="py-3 pr-3">{{ $lic->activeDeviceCount() }}/{{ $lic->max_devices }}</td>
                        <td class="py-3 pr-3 @if($lic->expired_at->isPast()) text-red-600 @endif">
                            {{ $lic->expired_at->format('d M Y') }}
                        </td>
                        <td class="py-3">
                            <a href="/admin/licenses/{{ $lic->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($licenses->hasPages())
        <div class="mt-4">
            {{ $licenses->links() }}
        </div>
    @endif
</x-layouts.admin>
