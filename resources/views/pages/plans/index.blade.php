<?php

use App\Models\SubscriptionPlan;
use App\Models\Product;
use Flux\Flux;
use Livewire\WithPagination;

use function Laravel\Folio\name;
use function Livewire\Volt\computed;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

name('plans.index');

uses(WithPagination::class);

state([
    'search' => '',
    'showDeleteModal' => false,
    'deletingPlanId' => null,
]);

$plans = computed(function () {
    return SubscriptionPlan::with('product')
        ->where('name', 'like', '%' . $this->search . '%')
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

    Flux::toast(variant: 'success', text: __('Plan deleted successfully.'));
};

?>

<x-layouts::app :title="__('Subscription Plans')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Subscription Plans') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Subscription Plans') }}</flux:heading>
                <flux:subheading>{{ __('Manage pricing plans for your products') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ url('/admin/plans/create') }}">
                {{ __('Add Plan') }}
            </flux:button>
        </div>

        {{-- Search --}}
        <flux:input size="md" wire:model.live="search" type="search" placeholder="{{ __('Search by plan name...') }}" />

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->plans">
                <flux:table.columns>
                    <flux:table.column>{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Plan Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Monthly') }}</flux:table.column>
                    <flux:table.column>{{ __('Yearly') }}</flux:table.column>
                    <flux:table.column>{{ __('Max Devices') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->plans as $plan)
                        <flux:table.row :key="$plan->id">
                            <flux:table.cell>
                                <flux:badge size="sm" inset="top bottom">
                                    {{ $plan->product->name }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $plan->name }}</flux:table.cell>
                            <flux:table.cell>{{ Number::currency($plan->monthly_price, 'IDR', 'id') }}</flux:table.cell>
                            <flux:table.cell>{{ Number::currency($plan->yearly_price, 'IDR', 'id') }}</flux:table.cell>
                            <flux:table.cell>{{ $plan->max_devices }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil"
                                        href="{{ url('/admin/plans/' . $plan->id . '/edit') }}" />
                                    <flux:button variant="ghost" size="sm" icon="trash"
                                        wire:click="confirmDelete({{ $plan->id }})" />
                                </div>
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
                        <flux:heading size="lg">{{ __('Delete Plan') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this plan? This action cannot be undone.') }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
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