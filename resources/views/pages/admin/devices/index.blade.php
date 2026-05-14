<?php

use App\Models\Device;
use function Livewire\Volt\{state};

state(['search' => '']);

$devices = fn() => Device::with('license')
    ->where('is_active', true)
    ->when($this->search, fn($q) => $q->where(function($q) {
        $q->where('device_name', 'like', '%'.$this->search.'%')
          ->orWhere('device_id', 'like', '%'.$this->search.'%');
    }))
    ->latest()
    ->paginate(20);
?>

<x-layouts.admin>
    <x-slot:header>Active Devices</x-slot:header>

    <div class="mb-4">
        <x-text-input wire:model="search" placeholder="Search device name or ID..." class="w-72" />
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="pb-3 pr-4">Device Name</th>
                    <th class="pb-3 pr-4">Device ID</th>
                    <th class="pb-3 pr-4">License</th>
                    <th class="pb-3 pr-4">IP Address</th>
                    <th class="pb-3 pr-4">Activated</th>
                    <th class="pb-3">Last Seen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($devices as $device)
                    <tr class="border-b last:border-0">
                        <td class="py-3 pr-4">{{ $device->device_name }}</td>
                        <td class="py-3 pr-4 font-mono text-xs">{{ $device->device_id }}</td>
                        <td class="py-3 pr-4">
                            <a href="/admin/licenses/{{ $device->license_id }}/edit" class="text-blue-600 hover:underline font-mono text-xs">
                                {{ $device->license->license_key }}
                            </a>
                        </td>
                        <td class="py-3 pr-4">{{ $device->ip_address ?? '-' }}</td>
                        <td class="py-3 pr-4">{{ $device->activated_at->format('d M Y') }}</td>
                        <td class="py-3">{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($devices->hasPages())
        <div class="mt-4">
            {{ $devices->links() }}
        </div>
    @endif
</x-layouts.admin>
