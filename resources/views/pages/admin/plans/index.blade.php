<?php

use App\Models\SubscriptionPlan;
use function Livewire\Volt\{state};

state(['productFilter' => '']);

$plans = fn() => SubscriptionPlan::with('product')
    ->when($this->productFilter, fn($q) => $q->where('product_id', $this->productFilter))
    ->latest()
    ->get();

$deletePlan = function (int $id) {
    SubscriptionPlan::findOrFail($id)->delete();
    session()->flash('success', 'Plan deleted.');
};
?>

<x-layouts.admin>
    <x-slot:header>Subscription Plans</x-slot:header>

    <div class="flex justify-end mb-4">
        <a href="/admin/plans/create">
            <x-primary-button type="button">Create Plan</x-primary-button>
        </a>
    </div>

    @if($plans->isEmpty())
        <p class="text-gray-500">No plans yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="pb-3 pr-4">Product</th>
                        <th class="pb-3 pr-4">Name</th>
                        <th class="pb-3 pr-4">Duration</th>
                        <th class="pb-3 pr-4">Price</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plans as $plan)
                        <tr class="border-b last:border-0">
                            <td class="py-3 pr-4">{{ $plan->product->name }}</td>
                            <td class="py-3 pr-4">{{ $plan->name }}</td>
                            <td class="py-3 pr-4">{{ $plan->duration_days }} days</td>
                            <td class="py-3 pr-4">Rp {{ number_format($plan->price, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4">
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-green-100 text-green-800' => $plan->is_active,
                                    'bg-gray-100 text-gray-800' => !$plan->is_active,
                                ])>
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3">
                                <a href="/admin/plans/{{ $plan->id }}/edit" class="text-blue-600 hover:underline">Edit</a>
                                <button wire:click="deletePlan({{ $plan->id }})" class="text-red-600 hover:underline ml-3"
                                    onclick="return confirm('Delete plan \'{{ $plan->name }}\'?')">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
