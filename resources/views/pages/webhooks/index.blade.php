<?php

use App\Models\WebhookEndpoint;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state, uses};

name('webhooks.index');
middleware('check.admin');

uses(WithPagination::class);

state([
    'search' => '',
    'showDeleteModal' => false,
    'deletingId' => null,
]);

$endpoints = computed(function () {
    return WebhookEndpoint::where('url', 'like', '%'.$this->search.'%')
        ->latest()
        ->paginate(15);
});

$confirmDelete = function ($id) {
    $this->deletingId = $id;
    $this->showDeleteModal = true;
};

$delete = function () {
    WebhookEndpoint::findOrFail($this->deletingId)->delete();

    $this->deletingId = null;
    $this->showDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('Webhook endpoint deleted successfully.'));
};

?>

<x-layouts::app :title="__('Webhooks')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Webhooks') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Webhooks') }}</flux:heading>
                <flux:subheading>{{ __('Manage webhook endpoints for event notifications') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ route('webhooks.create') }}">
                {{ __('Add Webhook') }}
            </flux:button>
        </div>

        <flux:input size="md" wire:model.live="search" type="search"
            placeholder="{{ __('Search by URL...') }}" />

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->endpoints">
                <flux:table.columns>
                    <flux:table.column>{{ __('URL') }}</flux:table.column>
                    <flux:table.column>{{ __('Events') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Created') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->endpoints as $endpoint)
                        <flux:table.row :key="$endpoint->id">
                            <flux:table.cell class="max-w-xs truncate font-mono text-xs">
                                {{ $endpoint->url }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($endpoint->events as $event)
                                        <flux:badge size="sm" inset="top bottom">{{ $event }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$endpoint->is_active ? 'green' : 'gray'" size="sm" inset="top bottom">
                                    {{ $endpoint->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $endpoint->created_at?->diffForHumans() }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil"
                                        href="{{ route('webhooks.edit', ['webhook_endpoint' => $endpoint->id]) }}" />
                                    <flux:button variant="ghost" size="sm" icon="trash"
                                        wire:click="confirmDelete({{ $endpoint->id }})" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        <flux:modal wire:model.self="showDeleteModal" class="max-w-lg">
            <form wire:submit="delete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete Webhook Endpoint') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this webhook endpoint? Applications will no longer receive event notifications.') }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
    @endvolt
</x-layouts::app>
