<?php

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Services\LicenseKeyService;
use function Livewire\Volt\{state};

state([
    'product_id' => '',
    'customer_name' => '',
    'customer_email' => '',
    'max_devices' => 1,
    'started_at' => '',
    'plan_id' => '',
    'notes' => '',
]);

$products = Product::active()->get();

$plans = fn() => SubscriptionPlan::where('product_id', $this->product_id)
    ->where('is_active', true)
    ->get();

$save = function (LicenseKeyService $keyService) {
    $this->validate([
        'product_id' => 'required|exists:products,id',
        'customer_name' => 'required|max:255',
        'customer_email' => 'required|email|max:255',
        'max_devices' => 'required|integer|min:1|max:100',
        'started_at' => 'required|date',
        'plan_id' => 'required|exists:subscription_plans,id',
        'notes' => 'nullable|string',
    ]);

    $plan = SubscriptionPlan::findOrFail($this->plan_id);
    $startedAt = Carbon\Carbon::parse($this->started_at);
    $expiredAt = $startedAt->copy()->addDays($plan->duration_days);

    $license = License::create([
        'product_id' => $this->product_id,
        'customer_name' => $this->customer_name,
        'customer_email' => $this->customer_email,
        'license_key' => $keyService->generate(),
        'status' => LicenseStatus::Active,
        'max_devices' => $this->max_devices,
        'started_at' => $startedAt,
        'expired_at' => $expiredAt,
        'notes' => $this->notes ?: null,
    ]);

    $license->subscriptions()->create([
        'plan_id' => $this->plan_id,
        'status' => 'active',
        'starts_at' => $startedAt,
        'ends_at' => $expiredAt,
    ]);

    session()->flash('success', "License created: {$license->license_key}");
    $this->redirect('/admin/licenses');
};
?>

<x-layouts.admin>
    <x-slot:header>Create License</x-slot:header>

    <form wire:submit="save" class="max-w-lg space-y-4">
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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="customer_name" value="Customer Name" />
                <x-text-input wire:model="customer_name" id="customer_name" class="w-full mt-1" />
                <x-input-error for="customer_name" class="mt-1" />
            </div>
            <div>
                <x-input-label for="customer_email" value="Customer Email" />
                <x-text-input wire:model="customer_email" id="customer_email" type="email" class="w-full mt-1" />
                <x-input-error for="customer_email" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="plan_id" value="Subscription Plan" />
            <select wire:model="plan_id" id="plan_id"
                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select product first...</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} days - Rp {{ number_format($plan->price, 0, ',', '.') }})</option>
                @endforeach
            </select>
            <x-input-error for="plan_id" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="started_at" value="Start Date" />
                <x-text-input wire:model="started_at" id="started_at" type="date" class="w-full mt-1" />
                <x-input-error for="started_at" class="mt-1" />
            </div>
            <div>
                <x-input-label for="max_devices" value="Max Devices" />
                <x-text-input wire:model="max_devices" id="max_devices" type="number" min="1" class="w-full mt-1" />
                <x-input-error for="max_devices" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="notes" value="Notes (optional)" />
            <textarea wire:model="notes" id="notes" rows="2"
                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <x-input-error for="notes" class="mt-1" />
        </div>

        <div class="flex gap-4">
            <x-primary-button>Create License</x-primary-button>
            <a href="/admin/licenses">
                <x-secondary-button type="button">Cancel</x-secondary-button>
            </a>
        </div>
    </form>
</x-layouts.admin>
