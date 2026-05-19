<?php

use App\Models\License;
use App\Enums\LicenseStatus;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{uses, computed, state};

name('licenses.index');
middleware('check.admin');

uses(WithPagination::class);

state([
    'search' => '',
    'status' => '',
    'selected' => [],
    'showDeleteModal' => false,
    'deletingLicense' => null,
    'showBulkDeleteModal' => false,
    'showBulkDeleteAllModal' => false,
    'showBulkStatusModal' => false,
    'bulkStatusAction' => '',
]);

$licenses = computed(function () {
    $query = License::with('product');

    if ($this->search) {
        $query->where('key', 'like', '%' . $this->search . '%');
    }

    if ($this->status) {
        $query->where('status', $this->status);
    }

    return $query->latest()->paginate(15);
});

$statuses = computed(fn() => LicenseStatus::cases());

$confirmDelete = function ($id) {
    $this->deletingLicense = License::findOrFail($id);
    $this->showDeleteModal = true;
};

$delete = function () {
    if ($this->deletingLicense) {
        $this->deletingLicense->delete();

        $this->deletingLicense = null;
        $this->showDeleteModal = false;

        Flux::toast(duration: 1500, variant: 'success', text: __('License deleted.'));
    }
};

$toggleSelectAll = function () {
    $ids = $this->licenses->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    $selectedIds = collect($this->selected)->map(fn ($id) => (string) $id)->toArray();

    if (count(array_intersect($selectedIds, $ids)) === count($ids)) {
        $this->selected = array_values(array_diff($selectedIds, $ids));
    } else {
        $this->selected = array_values(array_unique(array_merge($selectedIds, $ids)));
    }
};

$confirmBulkDelete = function () {
    if (empty($this->selected)) {
        Flux::toast(duration: 1500, variant: 'warning', text: __('No items selected.'));

        return;
    }
    $this->showBulkDeleteModal = true;
};

$bulkDelete = function () {
    License::whereIn('id', $this->selected)->delete();
    $this->selected = [];
    $this->showBulkDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('Selected licenses deleted.'));
};

$confirmBulkDeleteAll = function () {
    $this->showBulkDeleteAllModal = true;
};

$bulkDeleteAll = function () {
    License::query()->delete();
    $this->selected = [];
    $this->showBulkDeleteAllModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('All licenses deleted.'));
};

$confirmBulkStatus = function ($action) {
    if (empty($this->selected)) {
        Flux::toast(duration: 1500, variant: 'warning', text: __('No items selected.'));

        return;
    }
    $this->bulkStatusAction = $action;
    $this->showBulkStatusModal = true;
};

$bulkUpdateStatus = function () {
    License::whereIn('id', $this->selected)->update(['status' => $this->bulkStatusAction]);
    $this->selected = [];
    $this->showBulkStatusModal = false;

    $label = $this->bulkStatusAction === 'active' ? __('activated') : __('suspended');
    Flux::toast(duration: 1500, variant: 'success', text: __('Selected licenses :label.', ['label' => $label]));
};

?>

<x-layouts::app :title="__('Licenses')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Licenses') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between" data-tour="licenses-header">
            <div>
                <flux:heading size="xl">{{ __('Licenses') }}</flux:heading>
                <flux:subheading>{{ __('Manage software license keys and activations') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ route('licenses.create') }}" data-tour="licenses-create">
                {{ __('Create License') }}
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:input size="md" wire:model.live="search" type="search"
                placeholder="{{ __('Search by license key...') }}" />
            <flux:select wire:model.live="status" data-tour="licenses-status">
                <option value="">{{ __('All Status') }}</option>
                @foreach ($this->statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700"
            data-tour="licenses-table">
            {{-- Bulk Actions Toolbar --}}
            @if (!empty($selected))
                <div
                    class="flex items-center justify-between border-b border-neutral-200 bg-zinc-50 px-4 py-2 dark:border-neutral-700 dark:bg-zinc-800/50">
                    <flux:text size="sm" class="font-medium">
                        {{ __(':count item(s) selected', ['count' => count($selected)]) }}
                    </flux:text>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="primary" color="green"
                            wire:click="confirmBulkStatus('active')">
                            {{ __('Activate') }}
                        </flux:button>
                        <flux:button size="sm" variant="primary" color="yellow"
                            wire:click="confirmBulkStatus('suspended')">
                            {{ __('Suspend') }}
                        </flux:button>
                        <flux:button size="sm" variant="danger" wire:click="confirmBulkDelete">
                            {{ __('Delete Selected') }}
                        </flux:button>
                        <flux:button size="sm" variant="danger" wire:click="confirmBulkDeleteAll">
                            {{ __('Delete All') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            <div class="p-6">
                <flux:table :paginate="$this->licenses">
                    <flux:table.columns>
                        <flux:table.column class="w-10">
                            <flux:checkbox wire:click="toggleSelectAll"
                                :checked="count($this->licenses) > 0 && collect($this->licenses->pluck('id'))->every(fn($id) => in_array((string) $id, array_map('strval', $this->selected ?? [])))" />
                        </flux:table.column>
                        <flux:table.column>{{ __('License Key') }}</flux:table.column>
                        <flux:table.column>{{ __('Product') }}</flux:table.column>
                        <flux:table.column>{{ __('Customer') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Devices') }}</flux:table.column>
                        <flux:table.column>{{ __('Expires At') }}</flux:table.column>
                        <flux:table.column>{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->licenses as $license)
                            <flux:table.row :key="$license->id"
                                class="{{ in_array((string) $license->id, array_map('strval', $this->selected ?? [])) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <flux:table.cell class="w-10">
                                    <flux:checkbox wire:model.live="selected" value="{{ $license->id }}" />
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded w-fit">
                                    <a href="{{ route('licenses.edit', ['id' => $license->id]) }}" class="hover:underline">
                                        {{ $license->key }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $license->product->name }}</flux:table.cell>
                                <flux:table.cell class="text-sm">
                                    {{ $license->customer_name }}
                                    @if ($license->customer_store)
                                        <span class="text-xs text-zinc-400">({{ $license->customer_store }})</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$license->status->value === 'active' ? 'green' : ($license->status->value === 'suspended' ? 'yellow' : 'red')" size="sm" inset="top bottom">
                                        {{ $license->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:text size="sm">
                                        {{ count($license->devices ?? []) }} / {{ $license->max_devices }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $license->expires_at?->format('Y-m-d') ?? __('Never') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye"
                                                href="{{ route('licenses.edit', ['id' => $license->id]) }}">
                                                {{ __('View') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="pencil"
                                                href="{{ route('licenses.edit', ['id' => $license->id]) }}">
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger"
                                                wire:click="confirmDelete({{ $license->id }})">
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>

        {{-- Single Delete Confirmation Modal --}}
        <flux:modal wire:model.self="showDeleteModal" class="max-w-lg">
            <form wire:submit="delete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete License') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this license? This will also remove all registered devices and cannot be undone.') }}
                        </flux:subheading>
                        @if ($deletingLicense)
                            <div class="mt-3 p-3 bg-zinc-100 dark:bg-zinc-900 rounded-lg">
                                <code class="text-sm font-mono">{{ $deletingLicense->id }}</code>
                                <p class="text-xs text-zinc-500 mt-1">{{ $deletingLicense->product->name }} &middot; {{ $deletingLicense->customer_name }}</p>
                            </div>
                        @endif
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

        {{-- Bulk Delete Confirmation Modal --}}
        <flux:modal wire:model.self="showBulkDeleteModal" class="max-w-lg">
            <form wire:submit="bulkDelete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete Selected Licenses') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete :count selected license(s)? This action cannot be undone.', ['count' => count($selected)]) }}
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

        {{-- Delete All Confirmation Modal --}}
        <flux:modal wire:model.self="showBulkDeleteAllModal" class="max-w-lg">
            <form wire:submit="bulkDeleteAll" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete All Licenses') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete ALL licenses? This action cannot be undone.') }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" type="submit">{{ __('Delete All') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        {{-- Bulk Status Update Confirmation Modal --}}
        <flux:modal wire:model.self="showBulkStatusModal" class="max-w-lg">
            <form wire:submit="bulkUpdateStatus" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-blue-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">
                            {{ $bulkStatusAction === 'active' ? __('Activate') : __('Suspend') }} {{ __('Licenses') }}
                        </flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to :action :count selected license(s)?', ['action' => $bulkStatusAction === 'active' ? __('activate') : __('suspend'), 'count' => count($selected)]) }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">
                        {{ $bulkStatusAction === 'active' ? __('Activate') : __('Suspend') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
    @endvolt
</x-layouts::app>
