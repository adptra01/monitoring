<?php

use App\Models\SubscriptionPlan;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state, uses};

name('plans.index');
middleware('check.admin');

uses(WithPagination::class);

state([
    'search' => '',
    'selected' => [],
    'showDeleteModal' => false,
    'deletingPlanId' => null,
    'showBulkDeleteModal' => false,
    'showBulkDeleteAllModal' => false,
    'showBulkToggleModal' => false,
    'bulkToggleValue' => true,
]);

$plans = computed(function () {
    return SubscriptionPlan::where('name', 'like', '%' . $this->search . '%')
        ->latest()
        ->paginate(15);
});

$confirmDelete = function ($id) {
    $this->deletingPlanId = $id;
    $this->showDeleteModal = true;
};

$delete = function () {
    SubscriptionPlan::findOrFail($this->deletingPlanId)->delete();

    $this->deletingPlanId = null;
    $this->showDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('Plan deleted successfully.'));
};

$toggleSelectAll = function () {
    $ids = $this->plans->pluck('id')->map(fn ($id) => (string) $id)->toArray();
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
    SubscriptionPlan::whereIn('id', $this->selected)->delete();
    $this->selected = [];
    $this->showBulkDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('Selected plans deleted.'));
};

$confirmBulkDeleteAll = function () {
    $this->showBulkDeleteAllModal = true;
};

$bulkDeleteAll = function () {
    SubscriptionPlan::query()->delete();
    $this->selected = [];
    $this->showBulkDeleteAllModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('All plans deleted.'));
};

$confirmBulkToggle = function ($value) {
    if (empty($this->selected)) {
        Flux::toast(duration: 1500, variant: 'warning', text: __('No items selected.'));

        return;
    }
    $this->bulkToggleValue = $value;
    $this->showBulkToggleModal = true;
};

$bulkToggle = function () {
    SubscriptionPlan::whereIn('id', $this->selected)->update(['is_active' => $this->bulkToggleValue]);
    $this->selected = [];
    $this->showBulkToggleModal = false;

    $label = $this->bulkToggleValue ? __('activated') : __('deactivated');
    Flux::toast(duration: 1500, variant: 'success', text: __('Selected plans :label.', ['label' => $label]));
};

?>

<x-layouts::app :title="__('Subscription Plans')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Subscription Plans') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div data-tour="plans-header" class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Subscription Plans') }}</flux:heading>
                <flux:subheading>{{ __('Manage pricing plans') }}</flux:subheading>
            </div>
            <flux:button data-tour="plans-create" variant="primary" icon="plus" href="{{ route('plans.create') }}">
                {{ __('Add Plan') }}
            </flux:button>
        </div>

        <flux:input size="md" wire:model.live="search" type="search" placeholder="{{ __('Search by plan name...') }}" />

        <div
            data-tour="plans-table"
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            {{-- Bulk Actions Toolbar --}}
            @if (!empty($selected))
                <div
                    class="flex items-center justify-between border-b border-neutral-200 bg-zinc-50 px-4 py-2 dark:border-neutral-700 dark:bg-zinc-800/50">
                    <flux:text size="sm" class="font-medium">
                        {{ __(':count item(s) selected', ['count' => count($selected)]) }}
                    </flux:text>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="primary" color="green"
                            wire:click="confirmBulkToggle(true)">
                            {{ __('Activate') }}
                        </flux:button>
                        <flux:button size="sm" variant="primary" color="red"
                            wire:click="confirmBulkToggle(false)">
                            {{ __('Deactivate') }}
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
                <flux:table :paginate="$this->plans">
                    <flux:table.columns>
                        <flux:table.column class="w-10">
                            <flux:checkbox wire:click="toggleSelectAll"
                                :checked="count($this->plans) > 0 && collect($this->plans->pluck('id'))->every(fn($id) => in_array((string) $id, array_map('strval', $this->selected ?? [])))" />
                        </flux:table.column>
                        <flux:table.column>{{ __('Plan Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Duration') }}</flux:table.column>
                        <flux:table.column>{{ __('Active') }}</flux:table.column>
                        <flux:table.column data-tour="plans-actions">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->plans as $plan)
                            <flux:table.row :key="$plan->id"
                                class="{{ in_array((string) $plan->id, array_map('strval', $this->selected ?? [])) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <flux:table.cell class="w-10">
                                    <flux:checkbox wire:model.live="selected" value="{{ $plan->id }}" />
                                </flux:table.cell>
                                <flux:table.cell class="font-medium">{{ $plan->name }}</flux:table.cell>
                                <flux:table.cell>{{ $plan->duration_days }} {{ __('days') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$plan->is_active ? 'green' : 'red'" size="sm" inset="top bottom">
                                        {{ $plan->is_active ? __('Yes') : __('No') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="sm" icon="pencil"
                                            href="{{ route('plans.edit', ['plan' => $plan->id]) }}" />
                                        <flux:button variant="ghost" size="sm" icon="trash"
                                            wire:click="confirmDelete({{ $plan->id }})" />
                                    </div>
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
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete Plan') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this plan? This action cannot be undone.') }}
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

        {{-- Bulk Delete Confirmation Modal --}}
        <flux:modal wire:model.self="showBulkDeleteModal" class="max-w-lg">
            <form wire:submit="bulkDelete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete Selected Plans') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete :count selected plan(s)? This action cannot be undone.', ['count' => count($selected)]) }}
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
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete All Plans') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete ALL plans? This action cannot be undone.') }}
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

        {{-- Bulk Toggle Status Confirmation Modal --}}
        <flux:modal wire:model.self="showBulkToggleModal" class="max-w-lg">
            <form wire:submit="bulkToggle" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-blue-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">
                            {{ $bulkToggleValue ? __('Activate') : __('Deactivate') }} {{ __('Plans') }}
                        </flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to :action :count selected plan(s)?', ['action' => $bulkToggleValue ? __('activate') : __('deactivate'), 'count' => count($selected)]) }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">
                        {{ $bulkToggleValue ? __('Activate') : __('Deactivate') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
    @endvolt
</x-layouts::app>
