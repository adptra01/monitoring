<?php

use App\Models\AuditLog;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\name;
use function Livewire\Volt\{uses, computed, state};

name('admin.audit-logs.index');

uses(WithPagination::class);

state([
    'search' => '',
    'action' => '',
]);

$logs = computed(function () {
    return AuditLog::with('user')
        ->when($this->action, fn ($q) => $q->where('action', $this->action))
        ->when($this->search, fn ($q) => $q->where('ip_address', 'like', '%' . $this->search . '%')->orWhere('entity_type', 'like', '%' . $this->search . '%'))
        ->latest()
        ->paginate(25);
});

$actions = computed(fn () => [
    'created', 
    'device_registered', 
    'activation_request_created', 
    'activation_approved', 
    'activation_rejected', 
    'suspended', 
    'revoked', 
    'restored'
]);

?>

<x-layouts::app :title="__('Audit Logs')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Audit Logs') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Audit Logs') }}</flux:heading>
                    <flux:subheading>{{ __('Track system activities and resource changes') }}</flux:subheading>
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex gap-4">
                <flux:input size="md" wire:model.live="search" type="search" placeholder="{{ __('Search by IP or Entity...') }}" class="flex-1" />
                <flux:select wire:model.live="action" class="max-w-xs">
                    <option value="">{{ __('All Actions') }}</option>
                    @foreach ($this->actions as $act)
                        <option value="{{ $act }}">{{ Str::headline($act) }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <flux:table :paginate="$this->logs">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Action') }}</flux:table.column>
                        <flux:table.column>{{ __('Entity') }}</flux:table.column>
                        <flux:table.column>{{ __('User') }}</flux:table.column>
                        <flux:table.column>{{ __('IP Address') }}</flux:table.column>
                        <flux:table.column>{{ __('Created At') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->logs as $log)
                            <flux:table.row :key="$log->id">
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" color="zinc">
                                        {{ Str::headline($log->action) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm" class="font-medium">
                                        {{ class_basename($log->entity_type) }}
                                    </flux:text>
                                    <flux:text size="xs" color="zinc">
                                        #{{ $log->entity_id }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $log->user?->email ?? __('System') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm" font="mono">
                                        {{ $log->ip_address }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $log->created_at->format('Y-m-d H:i') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endvolt
</x-layouts::app>