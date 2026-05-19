<?php

use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, computed};

name('licenses.create');
middleware('check.admin');

state([
    'product_id' => '',
    'subscription_plan_id' => '',
    'customer_name' => '',
    'customer_phone' => '',
    'customer_store' => '',
    'customer_address' => '',
    'status' => 'active',
    'max_devices' => 5,
    'starts_at' => now()->format('Y-m-d'),
    'expires_at' => '',
    'notes' => '',
]);

$products = computed(fn() => Product::where('is_active', true)->get());

$plans = computed(fn() => SubscriptionPlan::where('is_active', true)->get());

$selectedPlan = computed(fn() => $this->subscription_plan_id ? SubscriptionPlan::find($this->subscription_plan_id) : null);

$updatedSubscriptionPlanId = function () {
    $plan = $this->selectedPlan;
    if ($plan) {
        $this->starts_at = now()->format('Y-m-d');
        $this->expires_at = now()->addDays($plan->duration_days)->format('Y-m-d');
    }
};

$save = function () {
    $this->validate([
        'product_id' => 'required|exists:products,id',
        'customer_name' => 'required|string|max:255',
        'customer_phone' => 'required|string|max:255',
        'customer_store' => 'required|string|max:255',
        'status' => 'required',
        'max_devices' => 'required|integer|min:1',
        'starts_at' => 'nullable|date',
        'expires_at' => 'nullable|date|after_or_equal:starts_at',
        'notes' => 'nullable|string',
    ]);

    $license = License::create([
        'product_id' => $this->product_id,
        'subscription_plan_id' => $this->subscription_plan_id ?: null,
        'key' => License::generateKey(),
        'customer_name' => $this->customer_name,
        'customer_phone' => $this->customer_phone,
        'customer_store' => $this->customer_store,
        'customer_address' => $this->customer_address,
        'status' => $this->status,
        'max_devices' => $this->max_devices,
        'starts_at' => $this->starts_at ?: null,
        'expires_at' => $this->expires_at ?: null,
        'notes' => $this->notes ?: null,
    ]);

    Flux::toast(duration: 1500, variant: 'success', text: __('License created successfully: :key', ['key' => $license->key]));

    $this->redirect(route('licenses.index'));
};

?>

<x-layouts::app :title="__('Create License')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('licenses.index') }}">{{ __('Licenses') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Create License') }}</flux:heading>
                    <flux:subheading>{{ __('Issue a new software license to a user') }}</flux:subheading>
                </div>
            </div>

            <div class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:select wire:model.live="product_id" :label="__('Product')" required autofocus>
                        <option value="">{{ __('Select Product') }}</option>
                        @foreach ($this->products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="subscription_plan_id" :label="__('Subscription Plan')">
                        <option value="">{{ __('No Plan (Custom)') }}</option>
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} {{ __('days') }})</option>
                        @endforeach
                    </flux:select>

                    @if ($selectedPlan)
                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3 text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-medium">{{ $selectedPlan->name }}</p>
                            <p class="mt-1">{{ __('Duration') }}: <strong>{{ $selectedPlan->duration_days }} {{ __('days') }}</strong></p>
                            <p>{{ __('License will auto-expire after the plan duration. You can adjust dates manually below.') }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:input wire:model="customer_name" :label="__('Customer Name')" required />
                        <flux:input wire:model="customer_phone" :label="__('Customer Phone')" required />
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:input wire:model="customer_store" :label="__('Store Name')" required />
                        <flux:input wire:model="customer_address" :label="__('Customer Address')" />
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:select wire:model="status" :label="__('Status')" required>
                            @foreach (App\Enums\LicenseStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="max_devices" type="number" min="1" :label="__('Max Devices')" required />
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <flux:input wire:model="starts_at" type="date" :label="__('Starts At')" />
                        <flux:input wire:model="expires_at" type="date" :label="__('Expires At')" />
                        <flux:input wire:model="notes" :label="__('Notes')" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('licenses.index') }}" variant="filled">{{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Create License') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
