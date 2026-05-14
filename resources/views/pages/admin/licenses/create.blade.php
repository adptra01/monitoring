<?php

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Enums\LicenseStatus;
use App\Enums\LicenseMode;
use Livewire\Volt\Component;

new class extends Component
{
    public ?Product $product = null;
    public ?User $user = null;
    public ?SubscriptionPlan $plan = null;

    public string $product_id = '';
    public string $user_id = '';
    public string $subscription_plan_id = '';
    public string $status = 'active';
    public string $mode = 'online';
    public int $max_devices = 1;
    public ?string $expires_at = '';

    public function mount(): void
    {
        $this->products = Product::where('is_active', true)->get();
    }

    public function updatedProductId(): void
    {
        $this->plans = SubscriptionPlan::where('product_id', $this->product_id)
            ->where('is_active', true)
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'required',
            'mode' => 'required',
            'max_devices' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $license = License::create([
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'subscription_plan_id' => $this->subscription_plan_id ?: null,
            'key' => License::generateKey(),
            'status' => $this->status,
            'mode' => $this->mode,
            'max_devices' => $this->max_devices,
            'expires_at' => $this->expires_at,
        ]);

        session()->flash('message', 'License created successfully: ' . $license->key);
        $this->redirect('/admin/licenses');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create License</h1>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
            <select wire:model="product_id" class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Select Product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            @error('product_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
            <select wire:model="user_id" class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Select User</option>
                @foreach (\App\Models\User::all() as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select wire:model="status" class="w-full rounded-md border-gray-300 shadow-sm">
                @foreach (App\Enums\LicenseStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
            <select wire:model="mode" class="w-full rounded-md border-gray-300 shadow-sm">
                @foreach (App\Enums\LicenseMode::cases() as $mode)
                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Devices</label>
            <input type="number" wire:model="max_devices" min="1" class="w-full rounded-md border-gray-300 shadow-sm">
            @error('max_devices') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
            <input type="date" wire:model="expires_at" class="w-full rounded-md border-gray-300 shadow-sm">
        </div>

        <div class="flex justify-end">
            <a href="{{ url('/admin/licenses') }}" class="px-4 py-2 text-gray-700 mr-2">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Create License
            </button>
        </div>
    </form>
</div>