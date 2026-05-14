<?php

use App\Models\Product;
use Illuminate\Support\Str;
use function Livewire\Volt\{state};

state(['name' => '', 'description' => '', 'is_active' => true]);

$product = Product::findOrFail((int) $product);

$name = $product->name;
$description = $product->description;
$is_active = $product->is_active;

$update = function () use ($product) {
    $this->validate([
        'name' => 'required|max:255|unique:products,name,'.$product->id,
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ]);

    $product->update([
        'name' => $this->name,
        'slug' => Str::slug($this->name),
        'description' => $this->description,
        'is_active' => $this->is_active,
    ]);

    session()->flash('success', 'Product updated.');
    $this->redirect('/admin/products');
};
?>

<x-layouts.admin>
    <x-slot:header>Edit Product</x-slot:header>

    <form wire:submit="update" class="max-w-lg space-y-4">
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" class="w-full mt-1" />
            <x-input-error for="name" class="mt-1" />
            @if($name)
                <p class="text-sm text-gray-500 mt-1">Slug: {{ Str::slug($name) }}</p>
            @endif
        </div>

        <div>
            <x-input-label for="description" value="Description" />
            <textarea wire:model="description" id="description" rows="3"
                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <x-input-error for="description" class="mt-1" />
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>

        <div class="flex gap-4">
            <x-primary-button>Update</x-primary-button>
            <a href="/admin/products">
                <x-secondary-button type="button">Cancel</x-secondary-button>
            </a>
        </div>
    </form>
</x-layouts.admin>
