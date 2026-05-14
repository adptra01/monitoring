<?php

use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?SubscriptionPlan $plan = null;
    public string $product_id = '';
    public string $name = '';
    public string $slug = '';
    public ?string $description = '';
    public ?string $monthly_price = '';
    public ?string $yearly_price = '';
    public int $max_devices = 1;
    public bool $is_active = true;
    public bool $is_default = false;
    public array $products = [];

    public function mount(int $plan): void
    {
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
        $this->products = Product::where('is_active', true)->get()->toArray();
    }

    public function updatedName(): void
    {
        if ($this->name !== $this->plan->name) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
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

        session()->flash('message', 'Plan updated successfully.');
        $this->redirect('/admin/plans');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Subscription Plan</h1>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
            <select wire:model="product_id" class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Select Product</option>
                @foreach ($products as $product)
                    <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                @endforeach
            </select>
            @error('product_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" wire:model="name"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" wire:model="slug"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('slug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea wire:model="description" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Price</label>
                <input type="number" step="0.01" wire:model="monthly_price" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Yearly Price</label>
                <input type="number" step="0.01" wire:model="yearly_price" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Devices</label>
            <input type="number" wire:model="max_devices" min="1" class="w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="is_default" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <span class="ml-2 text-sm text-gray-700">Default Plan</span>
            </label>
        </div>

        <div class="flex justify-end">
            <a href="{{ url('/admin/plans') }}" class="px-4 py-2 text-gray-700 mr-2">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Update Plan
            </button>
        </div>
    </form>
</div>