<?php

use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component
{
    public ?Product $product = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = '';
    public bool $is_active = true;

    public function mount(string $product): void
    {
        $this->product = Product::where('slug', $product)->firstOrFail();
        $this->name = $this->product->name;
        $this->slug = $this->product->slug;
        $this->description = $this->product->description;
        $this->is_active = $this->product->is_active;
    }

    public function updatedName(): void
    {
        if ($this->name !== $this->product->name) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $this->product->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $this->product->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message', 'Product updated successfully.');
        $this->redirect('/admin/products');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Product</h1>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-2xl">
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

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
        </div>

        <div class="flex justify-end">
            <a href="{{ url('/admin/products') }}" class="px-4 py-2 text-gray-700 mr-2">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Update Product
            </button>
        </div>
    </form>
</div>