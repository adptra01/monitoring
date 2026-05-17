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
    'duration_days' => 30,
    'is_active' => true,
]);

$products = computed(fn() => Product::where('is_active', true)->get());

mount(function (int $plan) {
    $this->plan = SubscriptionPlan::findOrFail($plan);
    $this->product_id = (string) $this->plan->product_id;
    $this->name = $this->plan->name;
    $this->slug = $this->plan->slug;
    $this->description = $this->plan->description;
    $this->duration_days = $this->plan->duration_days;
    $this->is_active = $this->plan->is_active;
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
        'description' => 'nullable|string',
        'duration_days' => 'required|integer|min:1',
        'is_active' => 'boolean',
    ]);

    $this->plan->update([
        'product_id' => $this->product_id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'duration_days' => $this->duration_days,
        'is_active' => $this->is_active,
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

            <div class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
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

                    <flux:input wire:model="duration_days" type="number" min="1" :label="__('Duration (Days)')"
                        required />

                    <flux:checkbox wire:model="is_active" :label="__('Active')" />

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('plans.index') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Update Plan') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
