<?php

use App\Models\License;
use App\Enums\LicenseStatus;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\name;
use function Livewire\Volt\{uses, computed, state};

name('licenses.index');

uses(WithPagination::class);

state([
    'search' => '',
    'status' => '',
]);

$licenses = computed(function () {
    $query = License::with(['product', 'user', 'devices']);

    if ($this->search) {
        $query->where('key', 'like', '%' . $this->search . '%');
    }

    if ($this->status) {
        $query->where('status', $this->status);
    }

    return $query->latest()->paginate(15);
});

$statuses = computed(fn() => LicenseStatus::cases());

?>

<x-layouts::app :title="__('Licenses')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Licenses') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Licenses') }}</flux:heading>
                <flux:subheading>{{ __('Manage software license keys and activations') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ url('/admin/licenses/create') }}">
                {{ __('Create License') }}
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex gap-4">
            <flux:input size="md" wire:model.live="search" type="search"
                placeholder="{{ __('Search by license key...') }}" class="flex-1" />
            <flux:select wire:model.live="status" class="max-w-xs">
                <option value="">{{ __('All Status') }}</option>
                @foreach ($this->statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->licenses">
                <flux:table.columns>
                    <flux:table.column>{{ __('License Key') }}</flux:table.column>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('User') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Devices') }}</flux:table.column>
                    <flux:table.column>{{ __('Expires At') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->licenses as $license)
                        <flux:table.row :key="$license->id">
                            <flux:table.cell>
                                <code
                                    class="text-sm font-mono bg-zinc-100 px-2 py-0.5 rounded dark:bg-zinc-800">{{ $license->key }}</code>
                            </flux:table.cell>
                            <flux:table.cell>{{ $license->product->name }}</flux:table.cell>
                            <flux:table.cell>{{ $license->user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$license->status === 'active' ? 'green' : 'red'" size="sm"
                                    inset="top bottom">
                                    {{ $license->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text size="sm">
                                    {{ $license->devices->count() }} / {{ $license->max_devices }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $license->expires_at?->format('Y-m-d') ?? __('Never') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
    @endvolt
</x-layouts::app>