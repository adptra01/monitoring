<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use function Livewire\Volt\{state};

state([
    'product_id' => '',
    'name' => '',
    'duration_days' => 30,
    'price' => '',
    'is_active' => true,
]);

$plan = SubscriptionPlan::findOrFail((int) $plan);
$products = Product::active()->get();

$product_id = $plan->product_id;
$name = $plan->name;
$duration_days = $plan->duration_days;
$price = $plan->price;
$is_active = $plan->is_active;

$update = function () use ($plan) {
    $this->validate([
        'product_id' => 'required|exists:products,id',
        'name' => 'required|max:255',
        'duration_days' => 'required|integer|min:1',
        'price' => 'required|numeric|min:0',
        'is_active' => 'boolean',
    ]);

    $plan->update([
        'product_id' => $this->product_id,
        'name' => $this->name,
        'duration_days' => $this->duration_days,
        'price' => $this->price,
        'is_active' => $this->is_active,
    ]);

    session()->flash('success', 'Plan updated.');
    $this->redirect('/admin/plans');
};
?>

<x-layouts.admin>
    <x-slot:header>Edit Plan</x-slot:header>

    <form wire:submit="update" class="max-w-lg space-y-4">
        <div>
            <x-input-label for="product_id" value="Product" />
            <select wire:model="product_id" id="product_id"
                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select product...</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <x-input-error for="product_id" class="mt-1" />
        </div>

        <div>
            <x-input-label for="name" value="Plan Name" />
            <x-text-input wire:model="name" id="name" class="w-full mt-1" />
            <x-input-error for="name" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="duration_days" value="Duration (days)" />
                <x-text-input wire:model="duration_days" id="duration_days" type="number" class="w-full mt-1" />
                <x-input-error for="duration_days" class="mt-1" />
            </div>
            <div>
                <x-input-label for="price" value="Price (Rp)" />
                <x-text-input wire:model="price" id="price" type="number" step="0.01" class="w-full mt-1" />
                <x-input-error for="price" class="mt-1" />
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                <span class="text-sm font-medium">Active</span>
            </label>
        </div>

        <div class="flex gap-4">
            <x-primary-button>Update</x-primary-button>
            <a href="/admin/plans">
                <x-secondary-button type="button">Cancel</x-secondary-button>
            </a>
        </div>
    </form>
</x-layouts.admin>
