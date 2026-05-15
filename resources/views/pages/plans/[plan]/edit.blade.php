<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, computed};

name('plans.edit');
middleware('check.admin');

state([
    'plan' => null,
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

$products = computed(fn() => Product::where('is_active', true)->get());

mount(function (int $plan) {
    $this->plan = SubscriptionPlan::findOrFail($plan);
    $this->product_id = (string) $this->plan->product_id;
    $this->name = $this->plan->name;
    $this->slug = $this->plan->slug;
    $this->description = $this->plan->description;
    $this->monthly_price = $this->plan->monthly_price;
    $this->yearly_price = $this->plan->yearly_price;
    $this->max_devices = $this->plan->max_devices;
    $this->is_active = $this->plan->is_active;
    $this->is_default = $this->plan->is_default;
});

$updatedName = function () {
    if ($this->name !== $this->plan->name) {
        $this->slug = Str::slug($this->name);
    }
};

$save = function () {
    $this->validate([
        'product_id' => 'required|exists:products,id',
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:subscription_plans,slug,' . $this->plan->id,
        'monthly_price' => 'nullable|numeric|min:0',
        'yearly_price' => 'nullable|numeric|min:0',
        'max_devices' => 'required|integer|min:1',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ]);

    $this->plan->update([
        'product_id' => $this->product_id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'monthly_price' => $this->monthly_price,
        'yearly_price' => $this->yearly_price,
        'max_devices' => $this->max_devices,
        'is_active' => $this->is_active,
        'is_default' => $this->is_default,
    ]);

    Flux::toast(duration: 1500, variant: 'success', text: __('Plan updated successfully.'));

    $this->redirect(route('plans.index'));
};

?>

<x-layouts::app :title="__('Edit Subscription Plan')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('plans.index') }}">{{ __('Plans') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Edit Subscription Plan') }}</flux:heading>
                <flux:subheading>{{ __('Update details for :name', ['name' => $plan->name]) }}</flux:subheading>
            </div>
        </div>

        <div
            class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
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
                    <flux:input wire:model="monthly_price" type="number" step="0.01"
                        :label="__('Monthly Price (IDR)')" />
                    <flux:input wire:model="yearly_price" type="number" step="0.01" :label="__('Yearly Price (IDR)')" />
                </div>

                <flux:input wire:model="max_devices" type="number" min="1" :label="__('Max Devices')" required />

                <div class="flex gap-6">
                    <flux:checkbox wire:model="is_active" :label="__('Active')" />
                    <flux:checkbox wire:model="is_default" :label="__('Default Plan')" />
                </div>

                <div class="flex justify-end gap-2 ">
                    <flux:button href="{{ route('plans.index') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Update Plan') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endvolt
</x-layouts::app>