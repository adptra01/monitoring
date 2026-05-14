<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Flux\Flux;

use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount, computed};

name('admin.plans.create');

state([
    'product_id' => '',
    'name' => '',
    'slug' => '',
    'description' => '',
    'monthly_price' => '',
    'yearly_price' => '',
    'max_devices' => 1,
    'is_active' => true,
    'is_default' => false,
]);

$products = computed(fn () => Product::where('is_active', true)->get());

$updatedName = function () {
    $this->slug = Str::slug($this->name);
};

$save = function () {
    $this->validate([
        'product_id' => 'required|exists:products,id',
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:subscription_plans,slug',
        'monthly_price' => 'nullable|numeric|min:0',
        'yearly_price' => 'nullable|numeric|min:0',
        'max_devices' => 'required|integer|min:1',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ]);

    SubscriptionPlan::create([
        'product_id' => $this->product_id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'monthly_price' => $this->monthly_price,
        'yearly_price' => $this->yearly_price,
        'max_devices' => $this->max_devices,
        'features' => ['Feature 1', 'Feature 2'],
        'is_active' => $this->is_active,
        'is_default' => $this->is_default,
    ]);

    Flux::toast(variant: 'success', text: __('Plan created successfully.'));
    
    $this->redirect('/admin/plans');
};

?>

<x-layouts::app :title="__('Create Subscription Plan')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ url('/admin/plans') }}">{{ __('Plans') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Create Subscription Plan') }}</flux:heading>
                    <flux:subheading>{{ __('Add a new pricing plan for your products') }}</flux:subheading>
                </div>
            </div>

            <div class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:select wire:model="product_id" :label="__('Product')" required autofocus>
                        <option value="">{{ __('Select Product') }}</option>
                        @foreach ($this->products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live.debounce.500ms="name" :label="__('Plan Name')" required />
                    
                    <flux:input wire:model="slug" :label="__('Slug')" required />

                    <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:input wire:model="monthly_price" type="number" step="0.01" :label="__('Monthly Price (IDR)')" />
                        <flux:input wire:model="yearly_price" type="number" step="0.01" :label="__('Yearly Price (IDR)')" />
                    </div>

                    <flux:input wire:model="max_devices" type="number" min="1" :label="__('Max Devices')" required />

                    <div class="flex gap-6">
                        <flux:checkbox wire:model="is_active" :label="__('Active')" />
                        <flux:checkbox wire:model="is_default" :label="__('Default Plan')" />
                    </div>

                    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button href="{{ url('/admin/plans') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Create Plan') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>