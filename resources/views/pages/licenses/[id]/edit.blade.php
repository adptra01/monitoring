<?php

use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Services\LicenseService;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount, computed};

name('licenses.edit');
middleware('check.admin');

state([
    'license' => null,
    'editing' => false,
    'edit_product_id' => '',
    'edit_subscription_plan_id' => '',
    'edit_customer_name' => '',
    'edit_customer_phone' => '',
    'edit_customer_store' => '',
    'edit_customer_address' => '',
    'edit_max_devices' => 5,
    'edit_starts_at' => '',
    'edit_expires_at' => '',
    'edit_notes' => '',
]);

mount(function (string $id) {
    $this->license = License::where('key', $id)->orWhere('id', $id)->firstOrFail();
});

$fillEdit = function () {
    $this->edit_product_id = (string) $this->license->product_id;
    $this->edit_subscription_plan_id = (string) ($this->license->subscription_plan_id ?? '');
    $this->edit_customer_name = $this->license->customer_name ?? '';
    $this->edit_customer_phone = $this->license->customer_phone ?? '';
    $this->edit_customer_store = $this->license->customer_store ?? '';
    $this->edit_customer_address = $this->license->customer_address ?? '';
    $this->edit_max_devices = $this->license->max_devices;
    $this->edit_starts_at = $this->license->starts_at?->format('Y-m-d') ?? '';
    $this->edit_expires_at = $this->license->expires_at?->format('Y-m-d') ?? '';
    $this->edit_notes = $this->license->notes ?? '';
};

$editProducts = computed(fn() => Product::where('is_active', true)->orderBy('name')->get());

$editPlans = computed(fn() => SubscriptionPlan::where('product_id', $this->edit_product_id)->where('is_active', true)->get());

$editSelectedPlan = computed(fn() => $this->edit_subscription_plan_id ? SubscriptionPlan::find($this->edit_subscription_plan_id) : null);

$updatedEditSubscriptionPlanId = function () {
    $plan = $this->editSelectedPlan;
    if ($plan) {
        $this->edit_starts_at = now()->format('Y-m-d');
        $this->edit_expires_at = now()->addDays($plan->duration_days)->format('Y-m-d');
    }
};

$toggleEdit = function () {
    if (!$this->editing) {
        $this->fillEdit();
    }
    $this->editing = !$this->editing;
};

$saveEdit = function () {
    $this->validate([
        'edit_product_id' => 'required|exists:products,id',
        'edit_customer_name' => 'required|string|max:255',
        'edit_customer_phone' => 'required|string|max:255',
        'edit_customer_store' => 'required|string|max:255',
        'edit_max_devices' => 'required|integer|min:1',
        'edit_starts_at' => 'nullable|date',
        'edit_expires_at' => 'nullable|date|after_or_equal:edit_starts_at',
        'edit_notes' => 'nullable|string',
    ]);

    $this->license->update([
        'product_id' => $this->edit_product_id,
        'subscription_plan_id' => $this->edit_subscription_plan_id ?: null,
        'customer_name' => $this->edit_customer_name,
        'customer_phone' => $this->edit_customer_phone,
        'customer_store' => $this->edit_customer_store,
        'customer_address' => $this->edit_customer_address,
        'max_devices' => $this->edit_max_devices,
        'starts_at' => $this->edit_starts_at ?: null,
        'expires_at' => $this->edit_expires_at ?: null,
        'notes' => $this->edit_notes ?: null,
    ]);

    $this->license->refresh();
    $this->editing = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('License updated.'));
};

$suspend = function (LicenseService $licenseService) {
    $licenseService->suspend($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'warning', text: __('License suspended.'));
};

$revoke = function (LicenseService $licenseService) {
    $licenseService->revoke($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'danger', text: __('License revoked.'));
};

$restore = function (LicenseService $licenseService) {
    $licenseService->restore($this->license);
    $this->license->refresh();
    Flux::toast(duration: 1500, variant: 'success', text: __('License restored.'));
};

$delete = function () {
    $this->license->delete();

    Flux::toast(duration: 1500, variant: 'success', text: __('License deleted.'));

    $this->redirect(route('licenses.index'));
};

?>

<x-layouts::app :title="__('License Details')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('licenses.index') }}">{{ __('Licenses') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Details') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('License Details') }}</flux:heading>
                    <flux:subheading>{{ __('View and manage license :key', ['key' => $license->key]) }}</flux:subheading>
                </div>

            </div>
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    @if ($license->status->value !== 'active')
                        <flux:button variant="primary" icon="check" wire:click="restore">
                            {{ __('Restore') }}
                        </flux:button>
                    @endif
                    @if ($license->status->value === 'active')
                        <flux:button variant="filled" icon="pause" wire:click="suspend">
                            {{ __('Suspend') }}
                        </flux:button>
                        <flux:button variant="danger" icon="trash" wire:click="revoke">
                            {{ __('Revoke') }}
                        </flux:button>
                    @endif
                    <flux:button variant="primary" icon="pencil" wire:click="toggleEdit">
                        {{ $editing ? __('Cancel') : __('Edit') }}
                    </flux:button>
                    <flux:button variant="danger" icon="trash" wire:click="delete"
                        wire:confirm="{{ __('Are you sure you want to delete this license?') }}">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>

            <div class="w-full grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="col-span-2 space-y-6">
                    @if ($editing)
                        <div
                            class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('Edit License') }}</flux:heading>
                            <form wire:submit="saveEdit" class="space-y-6">
                                <flux:select wire:model.live="edit_product_id" :label="__('Product')" required>
                                    <option value="">{{ __('Select Product') }}</option>
                                    @foreach ($this->editProducts as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>

                                @if ($edit_product_id)
                                    <flux:select wire:model.live="edit_subscription_plan_id" :label="__('Subscription Plan')">
                                        <option value="">{{ __('No Plan (Custom)') }}</option>
                                        @foreach ($this->editPlans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} {{ __('days') }})</option>
                                        @endforeach
                                    </flux:select>

                                    @if ($editSelectedPlan)
                                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3 text-sm text-blue-800 dark:text-blue-200">
                                            <p class="font-medium">{{ $editSelectedPlan->name }}</p>
                                            <p class="mt-1">{{ __('Duration') }}: <strong>{{ $editSelectedPlan->duration_days }} {{ __('days') }}</strong></p>
                                            <p>{{ __('License will auto-expire after the plan duration. You can adjust dates manually below.') }}</p>
                                        </div>
                                    @endif
                                @endif

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <flux:input wire:model="edit_customer_name" :label="__('Customer Name')" required />
                                    <flux:input wire:model="edit_customer_phone" :label="__('Customer Phone')" required />
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <flux:input wire:model="edit_customer_store" :label="__('Store Name')" required />
                                    <flux:input wire:model="edit_customer_address" :label="__('Customer Address')" />
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <flux:input wire:model="edit_max_devices" type="number" min="1"
                                        :label="__('Max Devices')" required />
                                    <flux:input wire:model="edit_starts_at" type="date" :label="__('Starts At')" />
                                    <flux:input wire:model="edit_expires_at" type="date" :label="__('Expires At')" />
                                </div>

                                <flux:input wire:model="edit_notes" :label="__('Notes')" />

                                <div class="flex justify-end gap-2">
                                    <flux:button variant="filled" wire:click="toggleEdit">{{ __('Cancel') }}</flux:button>
                                    <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div
                            class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('License Information') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-y-4 text-sm">
                                <div class="text-neutral-500">{{ __('Key') }}</div>
                                <div class="font-mono bg-neutral-100 dark:bg-zinc-900 px-2 py-1 rounded w-fit">
                                    {{ $license->key }}</div>

                                <div class="text-neutral-500">{{ __('Status') }}</div>
                                <div>
                                    <flux:badge :color="$license->status->value === 'active' ? 'green' : 'red'"
                                        size="sm">
                                        {{ $license->status->label() }}
                                    </flux:badge>
                                </div>

                                <div class="text-neutral-500">{{ __('Product') }}</div>
                                <div class="font-medium">{{ $license->product->name }}</div>

                                <div class="text-neutral-500">{{ __('Customer') }}</div>
                                <div>
                                    <div class="font-medium">{{ $license->customer_name }}</div>
                                    @if ($license->customer_store)
                                        <div class="text-neutral-400 text-xs">{{ $license->customer_store }}</div>
                                    @endif
                                </div>

                                <div class="text-neutral-500">{{ __('Phone') }}</div>
                                <div>{{ $license->customer_phone ?? '-' }}</div>

                                <div class="text-neutral-500">{{ __('Address') }}</div>
                                <div>{{ $license->customer_address ?? '-' }}</div>

                                <div class="text-neutral-500">{{ __('Max Devices') }}</div>
                                <div>{{ $license->max_devices }}</div>

                                <div class="text-neutral-500">{{ __('Registered Devices') }}</div>
                                <div>{{ count($license->devices ?? []) }}</div>

                                <div class="text-neutral-500">{{ __('Expires At') }}</div>
                                <div>{{ $license->expires_at?->format('d M Y') ?? __('Never') }}</div>

                                <div class="text-neutral-500">{{ __('Subscription Plan') }}</div>
                                <div>{{ $license->subscriptionPlan?->name ?? __('Custom / Manual') }}</div>

                                @if ($license->notes)
                                    <div class="text-neutral-500">{{ __('Notes') }}</div>
                                    <div class="text-sm">{{ $license->notes }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Registered Devices') }}</flux:heading>

                        @php $devices = $license->devices ?? []; @endphp
                        @if (empty($devices))
                            <flux:text class="text-center py-8">{{ __('No devices registered yet.') }}</flux:text>
                        @else
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>{{ __('Device Name') }}</flux:table.column>
                                    <flux:table.column>{{ __('Fingerprint') }}</flux:table.column>
                                    <flux:table.column>{{ __('Platform') }}</flux:table.column>
                                    <flux:table.column>{{ __('Last Seen') }}</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($devices as $device)
                                        <flux:table.row :key="$device['fingerprint'] ?? ''">
                                            <flux:table.cell class="font-medium">{{ $device['name'] ?? '-' }}
                                            </flux:table.cell>
                                            <flux:table.cell class="font-mono text-xs">
                                                {{ Str::limit($device['fingerprint'] ?? '', 16) }}</flux:table.cell>
                                            <flux:table.cell>{{ $device['platform'] ?? '-' }}</flux:table.cell>
                                            <flux:table.cell>
                                                {{ isset($device['last_seen_at']) ? \Carbon\Carbon::parse($device['last_seen_at'])->diffForHumans() : '-' }}
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endvolt
</x-layouts::app>
