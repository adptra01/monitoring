<?php

use App\Models\Product;
use Illuminate\Support\Str;
use Flux\Flux;

use function Laravel\Folio\name;
use function Livewire\Volt\{state, on};

name('products.create');

state([
    'name' => '',
    'slug' => '',
    'description' => '',
    'is_active' => true,
]);

$updatedName = function () {
    $this->slug = Str::slug($this->name);
};

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:products,slug',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ]);

    Product::create([
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'is_active' => $this->is_active,
    ]);

    Flux::toast(variant: 'success', text: __('Product created successfully.'));

    $this->redirect('/admin/products');
};

?>

<x-layouts::app :title="__('Create Product')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ url('/admin/products') }}">{{ __('Products') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Create Product') }}</flux:heading>
                <flux:subheading>{{ __('Add a new product to your catalog') }}</flux:subheading>
            </div>
        </div>

        <div
            class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model.live.debounce.500ms="name" :label="__('Name')" required autofocus />

                <flux:input wire:model="slug" :label="__('Slug')" required />

                <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

                <flux:checkbox wire:model="is_active" :label="__('Active')" />

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button href="{{ url('/admin/products') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Create Product') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endvolt
</x-layouts::app>