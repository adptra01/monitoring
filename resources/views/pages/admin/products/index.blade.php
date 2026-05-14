<?php

use App\Models\Product;
use function Livewire\Volt\{state};

$products = Product::withCount('subscriptionPlans', 'licenses')
    ->latest()
    ->get();

$deleteProduct = function (int $id) {
    $product = Product::findOrFail($id);
    $product->delete();
    session()->flash('success', 'Product deleted.');
};
?>

<x-layouts.admin>
    <x-slot:header>Products</x-slot:header>

    <div class="flex justify-end mb-4">
        <a href="/admin/products/create">
            <x-primary-button type="button">Create Product</x-primary-button>
        </a>
    </div>

    @if($products->isEmpty())
        <p class="text-gray-500">No products yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="pb-3 pr-4">Name</th>
                        <th class="pb-3 pr-4">Slug</th>
                        <th class="pb-3 pr-4">Plans</th>
                        <th class="pb-3 pr-4">Licenses</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-b last:border-0">
                            <td class="py-3 pr-4">{{ $product->name }}</td>
                            <td class="py-3 pr-4 text-gray-500">{{ $product->slug }}</td>
                            <td class="py-3 pr-4">{{ $product->subscription_plans_count }}</td>
                            <td class="py-3 pr-4">{{ $product->licenses_count }}</td>
                            <td class="py-3 pr-4">
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-green-100 text-green-800' => $product->is_active,
                                    'bg-gray-100 text-gray-800' => !$product->is_active,
                                ])>
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3">
                                <a href="/admin/products/{{ $product->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                                <button wire:click="deleteProduct({{ $product->id }})" class="text-red-600 hover:underline ml-3"
                                    onclick="return confirm('Delete product \'{{ $product->name }}\'?')">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
