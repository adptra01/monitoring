<?php

use App\Models\ApiClient;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state, uses};

name('api-clients.index');
middleware('check.admin');

uses(WithPagination::class);

state([
    'search' => '',
    'showDeleteModal' => false,
    'deletingClientId' => null,
    'showSecret' => [],
]);

$clients = computed(function () {
    return ApiClient::where('name', 'like', '%' . $this->search . '%')
        ->latest()
        ->paginate(15);
});

$toggleSecret = function ($id) {
    if (isset($this->showSecret[$id]) && $this->showSecret[$id]) {
        unset($this->showSecret[$id]);
    } else {
        $this->showSecret[$id] = true;
    }
};

$confirmDelete = function ($id) {
    $this->deletingClientId = $id;
    $this->showDeleteModal = true;
};

$delete = function () {
    ApiClient::findOrFail($this->deletingClientId)->delete();

    $this->deletingClientId = null;
    $this->showDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('API client deleted successfully.'));
};

?>

<x-layouts::app :title="__('API Clients')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('API Clients') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div data-tour="api-clients-header" class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('API Clients') }}</flux:heading>
                <flux:subheading>{{ __('Manage API credentials for client applications') }}</flux:subheading>
            </div>
            <flux:button data-tour="api-clients-create" variant="primary" icon="plus" href="{{ route('api-clients.create') }}">
                {{ __('Add API Client') }}
            </flux:button>
        </div>

        {{-- Search --}}
        <flux:input size="md" wire:model.live="search" type="search"
            placeholder="{{ __('Search by client name...') }}" />

        <div
            data-tour="api-clients-table"
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->clients">
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('API Key') }}</flux:table.column>
                    <flux:table.column>{{ __('Rate Limit') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Last Used') }}</flux:table.column>
                    <flux:table.column data-tour="api-clients-actions">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->clients as $client)
                        <flux:table.row :key="$client->id">
                            <flux:table.cell class="font-medium">{{ $client->name }}</flux:table.cell>
                            <flux:table.cell>
                                <code
                                    class="text-xs font-mono bg-zinc-100 px-2 py-0.5 rounded dark:bg-zinc-800">{{ $client->api_key }}</code>
                            </flux:table.cell>
                            <flux:table.cell>{{ $client->rate_limit }} req/m</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$client->is_active ? 'green' : 'gray'" size="sm" inset="top bottom">
                                    {{ $client->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $client->last_used_at?->diffForHumans() ?? __('Never') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="key"
                                        wire:click="toggleSecret({{ $client->id }})" />
                                    <flux:button variant="ghost" size="sm" icon="pencil"
                                        href="{{ route('api-clients.edit', ['api_client' => $client->id]) }}" />
                                    <flux:button variant="ghost" size="sm" icon="trash"
                                        wire:click="confirmDelete({{ $client->id }})" />
                                </div>
                                @if (isset($showSecret[$client->id]) && $showSecret[$client->id])
                                    <div class="mt-2">
                                        <flux:text size="xs" class="font-mono text-amber-600 dark:text-amber-400">
                                            {{ __('Secret') }}: {{ $client->api_secret }}
                                        </flux:text>
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Delete Confirmation Modal --}}
        <flux:modal wire:model.self="showDeleteModal" class="max-w-lg">
            <form wire:submit="delete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete API Client') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this API client? Applications using this key will stop working.') }}
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
