<?php

use App\Models\ActivationRequest;
use App\Services\LicenseService;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\name;
use function Livewire\Volt\{uses, computed, state};

name('activation-requests.index');

uses(WithPagination::class);

state([
    'status' => 'pending',
]);

$requests = computed(function () {
    return ActivationRequest::with(['license', 'device'])
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->latest()
        ->paginate(15);
});

$approve = function (int $id) {
    $request = ActivationRequest::findOrFail($id);
    app(LicenseService::class)->approveActivationRequest($request, auth()->id());

    Flux::toast(variant: 'success', text: __('Activation request approved.'));
};

$reject = function (int $id) {
    $request = ActivationRequest::findOrFail($id);
    app(LicenseService::class)->rejectActivationRequest($request, 'Rejected by admin', auth()->id());

    Flux::toast(variant: 'success', text: __('Activation request rejected.'));
};

?>

<x-layouts::app :title="__('Activation Requests')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Activation Requests') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Activation Requests') }}</flux:heading>
                <flux:subheading>{{ __('Approve or reject manual license activation requests') }}</flux:subheading>
            </div>
            <flux:select wire:model.live="status" class="max-w-xs">
                <option value="">{{ __('All') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </flux:select>
        </div>

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->requests">
                <flux:table.columns>
                    <flux:table.column>{{ __('ID') }}</flux:table.column>
                    <flux:table.column>{{ __('License') }}</flux:table.column>
                    <flux:table.column>{{ __('Device') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Code') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->requests as $request)
                        <flux:table.row :key="$request->id">
                            <flux:table.cell>{{ $request->id }}</flux:table.cell>
                            <flux:table.cell>
                                <code
                                    class="text-xs font-mono bg-zinc-100 px-2 py-0.5 rounded dark:bg-zinc-800">{{ $request->license->key }}</code>
                            </flux:table.cell>
                            <flux:table.cell>{{ $request->device->name ?? __('Unknown') }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $color = match ($request->status->value) {
                                        'pending' => 'yellow',
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        default => 'gray'
                                    };
                                @endphp
                                <flux:badge :color="$color" size="sm" inset="top bottom">
                                    {{ Str::headline($request->status->value) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <code class="text-xs">{{ $request->code }}</code>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($request->status->value === 'pending')
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="sm" icon="check"
                                            wire:click="approve({{ $request->id }})" />
                                        <flux:button variant="ghost" size="sm" icon="x-mark"
                                            wire:click="reject({{ $request->id }})" />
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
    @endvolt
</x-layouts::app>