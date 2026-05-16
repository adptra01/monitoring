<?php

use App\Models\ApiClient;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state};

name('api-clients.create');
middleware('check.admin');

state([
    'name' => '',
    'is_active' => true,
    'rate_limit' => 60,
    'generated_key' => '',
    'generated_secret' => '',
]);

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'is_active' => 'boolean',
        'rate_limit' => 'required|integer|min:1|max:10000',
    ]);

    $client = ApiClient::create([
        'name' => $this->name,
        'api_key' => ApiClient::generateApiKey(),
        'api_secret' => ApiClient::generateApiSecret(),
        'is_active' => $this->is_active,
        'rate_limit' => $this->rate_limit,
    ]);

    $this->generated_key = $client->api_key;
    $this->generated_secret = $client->api_secret;

    Flux::toast(duration: 5000, variant: 'success', text: __('API client created successfully. Save the secret now — it won\'t be shown again.'));
};

$done = function () {
    $this->redirect(route('api-clients.index'));
};

?>

<x-layouts::app :title="__('Create API Client')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('api-clients.index') }}">{{ __('API Clients') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Create API Client') }}</flux:heading>
                <flux:subheading>{{ __('Generate API credentials for a client application') }}</flux:subheading>
            </div>
        </div>

        @if ($generated_key)
            <div
                class="max-w-2xl rounded-xl border border-amber-200 dark:border-amber-700 p-6 bg-amber-50 dark:bg-amber-900/20">
                <flux:heading size="lg" class="text-amber-800 dark:text-amber-200">{{ __('API Credentials') }}</flux:heading>
                <flux:subheading class="mt-1 text-amber-700 dark:text-amber-300">
                    {{ __('Copy these credentials now. The secret will not be shown again.') }}
                </flux:subheading>

                <div class="mt-4 space-y-3">
                    <div>
                        <flux:text size="sm" class="font-semibold">{{ __('API Key') }}:</flux:text>
                        <code
                            class="block mt-1 text-sm font-mono bg-white dark:bg-zinc-800 px-3 py-2 rounded border border-amber-300 dark:border-amber-600 select-all">{{ $generated_key }}</code>
                    </div>
                    <div>
                        <flux:text size="sm" class="font-semibold">{{ __('API Secret') }}:</flux:text>
                        <code
                            class="block mt-1 text-sm font-mono bg-white dark:bg-zinc-800 px-3 py-2 rounded border border-amber-300 dark:border-amber-600 select-all">{{ $generated_secret }}</code>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="done">{{ __('Done, take me back') }}</flux:button>
                </div>
            </div>
        @else
            <div
                class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Client Name')" required autofocus
                        placeholder="e.g., My POS App" />

                    <flux:input wire:model="rate_limit" type="number" min="1" max="10000"
                        :label="__('Rate Limit (requests per minute)')" required />

                    <div>
                        <flux:checkbox wire:model="is_active" :label="__('Active')" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('api-clients.index') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Create API Client') }}</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    @endvolt
</x-layouts::app>
