<?php

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Enums\LicenseStatus;
use App\Enums\LicenseMode;
use Flux\Flux;

use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount, computed};

name('licenses.create');

state([
    'product_id' => '',
    'user_id' => '',
    'subscription_plan_id' => '',
    'status' => 'active',
    'mode' => 'online',
    'max_devices' => 1,
    'expires_at' => '',
]);

$products = computed(fn() => Product::where('is_active', true)->get());
$users = computed(fn() => User::all());
$plans = computed(fn() => SubscriptionPlan::where('product_id', $this->product_id)->where('is_active', true)->get());

$save = function () {
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
        'expires_at' => $this->expires_at ?: null,
    ]);

    Flux::toast(variant: 'success', text: __('License created successfully: :key', ['key' => $license->key]));

    $this->redirect('/licenses');
};

?>

<x-layouts::app :title="__('Create License')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ url('/licenses') }}">{{ __('Licenses') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Create License') }}</flux:heading>
                <flux:subheading>{{ __('Issue a new software license to a user') }}</flux:subheading>
            </div>
        </div>

        <div
            class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
            <form wire:submit="save" class="space-y-6">
                <flux:select wire:model.live="product_id" :label="__('Product')" required autofocus>
                    <option value="">{{ __('Select Product') }}</option>
                    @foreach ($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="user_id" :label="__('User')" required>
                    <option value="">{{ __('Select User') }}</option>
                    @foreach ($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </flux:select>

                @if ($product_id)
                    <flux:select wire:model="subscription_plan_id" :label="__('Subscription Plan')">
                        <option value="">{{ __('No Plan (Custom)') }}</option>
                        @foreach ($this->plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </flux:select>
                @endif

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model="status" :label="__('Status')" required>
                        @foreach (App\Enums\LicenseStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="mode" :label="__('Activation Mode')" required>
                        @foreach (App\Enums\LicenseMode::cases() as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:input wire:model="max_devices" type="number" min="1" :label="__('Max Devices')" required />
                    <flux:input wire:model="expires_at" type="date" :label="__('Expires At')" />
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:button href="{{ url('/licenses') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Create License') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endvolt
</x-layouts::app>
