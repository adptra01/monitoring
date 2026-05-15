<?php

use App\Models\License;
use App\Services\LicenseService;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};

name('licenses.edit');
middleware('check.admin');

state([
    'license' => null,
]);

mount(function (string $license) {
    $this->license = License::where('key', $license)->orWhere('id', $license)->firstOrFail();
});

$suspend = function (LicenseService $licenseService) {
    $licenseService->suspend($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'warning', text: __('License suspended.'));
};

$revoke = function (LicenseService $licenseService) {
    $licenseService->revoke($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'danger', text: __('License revoked.'));
};

$restore = function (LicenseService $licenseService) {
    $licenseService->restore($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'success', text: __('License restored.'));
};

?>

<x-layouts::app :title="__('License Details')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ url('/licenses') }}">{{ __('Licenses') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Details') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('License Details') }}</flux:heading>
                    <flux:subheading>{{ __('View and manage license :key', ['key' => $license->key]) }}</flux:subheading>
                </div>
                <div class="flex gap-2">
                    @if ($license->status->value !== 'active')
                        <flux:button variant="primary" icon="check" wire:click="restore">
                            {{ __('Restore') }}
                        </flux:button>
                    @endif
                    @if ($license->status->value === 'active')
                        <flux:button variant="filled" icon="pause" wire:click="suspend">
                            {{ __('Suspend') }}
                        </flux:button>
                        <flux:button variant="danger" icon="trash" wire:click="revoke">
                            {{ __('Revoke') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                {{-- Info Card --}}
                <div class="col-span-2 space-y-6">
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('License Information') }}</flux:heading>
                        
                        <div class="grid grid-cols-2 gap-y-4 text-sm">
                            <div class="text-neutral-500">{{ __('Key') }}</div>
                            <div class="font-mono bg-neutral-100 dark:bg-zinc-900 px-2 py-1 rounded w-fit">{{ $license->key }}</div>
                            
                            <div class="text-neutral-500">{{ __('Status') }}</div>
                            <div>
                                <flux:badge :color="$license->status->value === 'active' ? 'green' : 'red'" size="sm">
                                    {{ $license->status->label() }}
                                </flux:badge>
                            </div>
                            
                            <div class="text-neutral-500">{{ __('Activation Mode') }}</div>
                            <div>{{ $license->mode->label() }}</div>
                            
                            <div class="text-neutral-500">{{ __('Max Devices') }}</div>
                            <div>{{ $license->max_devices }}</div>
                            
                            <div class="text-neutral-500">{{ __('Expires At') }}</div>
                            <div>{{ $license->expires_at?->format('d M Y') ?? __('Never') }}</div>
                        </div>
                    </div>

                    {{-- Devices Table --}}
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Registered Devices') }}</flux:heading>
                        
                        @if ($license->devices->isEmpty())
                            <flux:text class="text-center py-8">{{ __('No devices registered yet.') }}</flux:text>
                        @else
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>{{ __('Device Name') }}</flux:table.column>
                                    <flux:table.column>{{ __('Fingerprint') }}</flux:table.column>
                                    <flux:table.column>{{ __('Platform') }}</flux:table.column>
                                    <flux:table.column>{{ __('Last Seen') }}</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($license->devices as $device)
                                        <flux:table.row :key="$device->id">
                                            <flux:table.cell class="font-medium">{{ $device->name }}</flux:table.cell>
                                            <flux:table.cell class="font-mono text-xs">{{ Str::limit($device->fingerprint, 16) }}</flux:table.cell>
                                            <flux:table.cell>{{ $device->platform }}</flux:table.cell>
                                            <flux:table.cell>{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>
                </div>

                {{-- User/Product Card --}}
                <div class="space-y-6">
                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Product & Owner') }}</flux:heading>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-neutral-500 text-xs uppercase tracking-wider mb-1">{{ __('Product') }}</div>
                                <div class="font-medium flex items-center gap-2">
                                    <flux:icon name="cube" variant="micro" />
                                    {{ $license->product->name }}
                                </div>
                            </div>
                            
                            <flux:separator />

                            <div>
                                <div class="text-neutral-500 text-xs uppercase tracking-wider mb-1">{{ __('Customer') }}</div>
                                <div class="font-medium">{{ $license->user->name }}</div>
                                <div class="text-neutral-400 text-xs">{{ $license->user->email }}</div>
                            </div>

                            <flux:separator />

                            <div>
                                <div class="text-neutral-500 text-xs uppercase tracking-wider mb-1">{{ __('Subscription Plan') }}</div>
                                <div class="font-medium">{{ $license->subscriptionPlan?->name ?? __('Custom / Manual') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endvolt
</x-layouts::app>