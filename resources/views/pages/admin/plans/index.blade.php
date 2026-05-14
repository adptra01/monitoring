<?php

use App\Models\SubscriptionPlan;
use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function delete(int $id): void
    {
        SubscriptionPlan::findOrFail($id)->delete();
        session()->flash('message', 'Plan deleted successfully.');
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Subscription Plans</h1>
        <a href="{{ url('/admin/plans/create') }}"
           class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Add Plan
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monthly</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Yearly</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Devices</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach (SubscriptionPlan::with('product')->latest()->paginate(15) as $plan)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $plan->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $plan->product->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $plan->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${{ $plan->monthly_price }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${{ $plan->yearly_price }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $plan->max_devices }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ url('/admin/plans/' . $plan->id . '/edit') }}"
                               class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <button wire:click="delete({{ $plan->id }})"
                                    wire:confirm="Are you sure?"
                                    class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ SubscriptionPlan::with('product')->latest()->paginate(15)->links() }}</div>
    </div>
</div>