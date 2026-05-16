<?php

use App\Models\Device;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{uses, computed, state};

name('devices.index');
middleware('check.admin');


uses(WithPagination::class);

state([
    'search' => '',
]);

$devices = computed(function () {
    return Device::with('license')
        ->when($this->search, fn($q) => $q->where('fingerprint', 'like', '%' . $this->search . '%')->orWhere('name', 'like', '%' . $this->search . '%'))
        ->latest()
        ->paginate(15);
});

?>

<x-layouts::app :title="__('Devices')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Devices') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div data-tour="devices-header" class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Devices') }}</flux:heading>
                <flux:subheading>{{ __('Monitor activated devices and hardware fingerprints') }}</flux:subheading>
            </div>
        </div>

        {{-- Search --}}
        <flux:input data-tour="devices-search" size="md" wire:model.live="search" type="search"
            placeholder="{{ __('Search by name or fingerprint...') }}" />

        <div
            data-tour="devices-table"
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->devices">
                <flux:table.columns>
                    <flux:table.column>{{ __('ID') }}</flux:table.column>
                    <flux:table.column>{{ __('License') }}</flux:table.column>
                    <flux:table.column>{{ __('Device Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Platform') }}</flux:table.column>
                    <flux:table.column>{{ __('Last Seen') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->devices as $device)
                        <flux:table.row :key="$device->id">
                            <flux:table.cell>{{ $device->id }}</flux:table.cell>
                            <flux:table.cell>
                                <code
                                    class="text-xs font-mono bg-zinc-100 px-2 py-0.5 rounded dark:bg-zinc-800">{{ $device->license->key }}</code>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $device->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" inset="top bottom">
                                    {{ $device->platform }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $device->last_seen_at?->format('Y-m-d H:i') ?? __('Never') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
    @endvolt
</x-layouts::app>