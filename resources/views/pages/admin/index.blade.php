<?php

use App\Models\License;
use App\Models\Device;
use App\Models\ActivationRequest;
use App\Models\Product;
use Flux\Flux;

use function Laravel\Folio\name;
use function Livewire\Volt\{computed};

name('admin.index');

$totalProducts = computed(fn () => Product::count());
$totalLicenses = computed(fn () => License::count());
$activeLicenses = computed(fn () => License::where('status', 'active')->count());
$pendingActivations = computed(fn () => ActivationRequest::where('status', 'pending')->count());
$totalDevices = computed(fn () => Device::count());

?>

<x-layouts::app :title="__('Admin Dashboard')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Admin Dashboard') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Admin Dashboard') }}</flux:heading>
                    <flux:subheading>{{ __('Overview of your licensing system') }}</flux:subheading>
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Products') }}</p>
                    <p class="mt-2 text-4xl font-bold">{{ $this->totalProducts }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Total Licenses') }}</p>
                    <p class="mt-2 text-4xl font-bold">{{ $this->totalLicenses }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Active Licenses') }}</p>
                    <p class="mt-2 text-4xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->activeLicenses }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Pending Activations') }}</p>
                    <p class="mt-2 text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $this->pendingActivations }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Total Devices') }}</p>
                <p class="mt-2 text-4xl font-bold">{{ $this->totalDevices }}</p>
            </div>
            
            {{-- Quick Links --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mt-4">
                <flux:button href="{{ url('/admin/products') }}" icon="shopping-bag">{{ __('Manage Products') }}</flux:button>
                <flux:button href="{{ url('/admin/licenses') }}" icon="key">{{ __('View Licenses') }}</flux:button>
                <flux:button href="{{ url('/admin/activation-requests') }}" icon="check-badge">{{ __('Pending Requests') }}</flux:button>
            </div>
        </div>
    @endvolt
</x-layouts::app>