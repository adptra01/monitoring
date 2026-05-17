<?php

use App\Models\License;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Enums\LicenseMode;
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
    'edit_mode' => 'online',
    'edit_max_devices' => 1,
    'edit_expires_at' => '',
]);

mount(function (string $license) {
    $licenseModel = License::with(['devices'])->where('key', $license)->orWhere('id', $license)->firstOrFail();
    $this->license = $licenseModel;
});

$fillEdit = function () {
    $this->edit_product_id = (string) $this->license->product_id;
    $this->edit_subscription_plan_id = (string) ($this->license->subscription_plan_id ?? '');
    $this->edit_mode = $this->license->mode->value;
    $this->edit_max_devices = $this->license->max_devices;
    $this->edit_expires_at = $this->license->expires_at?->format('Y-m-d') ?? '';
};

$editProducts = computed(fn() => Product::where('is_active', true)->orderBy('name')->get());

$editPlans = computed(fn() => SubscriptionPlan::where('product_id', $this->edit_product_id)->where('is_active', true)->get());

$toggleEdit = function () {
    if (! $this->editing) {
        $this->fillEdit();
    }
    $this->editing = ! $this->editing;
};

$saveEdit = function () {
    $this->validate([
        'edit_product_id' => 'required|exists:products,id',
        'edit_mode' => 'required',
        'edit_max_devices' => 'required|integer|min:1',
        'edit_expires_at' => 'nullable|date',
    ]);

    $this->license->update([
        'product_id' => $this->edit_product_id,
        'subscription_plan_id' => $this->edit_subscription_plan_id ?: null,
        'mode' => $this->edit_mode,
        'max_devices' => $this->edit_max_devices,
        'expires_at' => $this->edit_expires_at ?: null,
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
    $this->license->devices()->delete();
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

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="col-span-2 space-y-6">
                    @if ($editing)
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('Edit License') }}</flux:heading>
                            <form wire:submit="saveEdit" class="space-y-6">
                                <flux:select wire:model.live="edit_product_id" :label="__('Product')" required>
                                    <option value="">{{ __('Select Product') }}</option>
                                    @foreach ($this->editProducts as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </flux:select>

                                @if ($edit_product_id)
                                    <flux:select wire:model="edit_subscription_plan_id" :label="__('Subscription Plan')">
                                        <option value="">{{ __('No Plan (Custom)') }}</option>
                                        @foreach ($this->editPlans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                        @endforeach
                                    </flux:select>
                                @endif

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <flux:select wire:model="edit_mode" :label="__('Activation Mode')" required>
                                        @foreach (App\Enums\LicenseMode::cases() as $mode)
                                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                        @endforeach
                                    </flux:select>

                                    <flux:input wire:model="edit_max_devices" type="number" min="1" :label="__('Max Devices')" required />
                                </div>

                                <flux:input wire:model="edit_expires_at" type="date" :label="__('Expires At')" />

                                <div class="flex justify-end gap-2">
                                    <flux:button variant="filled" wire:click="toggleEdit">{{ __('Cancel') }}</flux:button>
                                    <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                            <flux:heading size="lg" class="mb-4">{{ __('License Information') }}</flux:heading>

                            <div class="grid grid-cols-2 gap-y-4 text-sm">
                                <div class="text-neutral-500">{{ __('Key') }}</div>
                                <div class="font-mono bg-neutral-100 dark:bg-zinc-900 px-2 py-1 rounded w-fit">{{ $license->key }}</div>

                                <div class="text-neutral-500">{{ __('Status') }}</div>
                                <div>
                                    <flux:badge :color="$license->status->value === 'active' ? 'green' : 'red'" size="sm">
                                        {{ $license->status->label() }}
                                    </flux:badge>
                                </div>

                                <div class="text-neutral-500">{{ __('Product') }}</div>
                                <div class="font-medium">{{ $license->product->name }}</div>

                                <div class="text-neutral-500">{{ __('Customer') }}</div>
                                <div>
                                    <div class="font-medium">{{ $license->user->name }}</div>
                                    <div class="text-neutral-400 text-xs">{{ $license->user->email }}</div>
                                </div>

                                <div class="text-neutral-500">{{ __('Activation Mode') }}</div>
                                <div>{{ $license->mode->label() }}</div>

                                <div class="text-neutral-500">{{ __('Max Devices') }}</div>
                                <div>{{ $license->max_devices }}</div>

                                <div class="text-neutral-500">{{ __('Registered Devices') }}</div>
                                <div>{{ $license->devices->count() }}</div>

                                <div class="text-neutral-500">{{ __('Expires At') }}</div>
                                <div>{{ $license->expires_at?->format('d M Y') ?? __('Never') }}</div>

                                <div class="text-neutral-500">{{ __('Subscription Plan') }}</div>
                                <div>{{ $license->subscriptionPlan?->name ?? __('Custom / Manual') }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-800 p-6">
                        <flux:heading size="lg" class="mb-4">{{ __('Registered Devices') }}</flux:heading>

                        @if ($license->devices->isEmpty())
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
                                    @foreach ($license->devices as $device)
                                        <flux:table.row :key="$device->id">
                                            <flux:table.cell class="font-medium">{{ $device->name }}</flux:table.cell>
                                            <flux:table.cell class="font-mono text-xs">{{ Str::limit($device->fingerprint, 16) }}</flux:table.cell>
                                            <flux:table.cell>{{ $device->platform }}</flux:table.cell>
                                            <flux:table.cell>{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</flux:table.cell>
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
