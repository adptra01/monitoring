<?php

use App\Models\ApiClient;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};

name('api-clients.edit');
middleware('check.admin');

state([
    'client' => null,
    'name' => '',
    'is_active' => true,
    'rate_limit' => 60,
    'allowed_ips' => '',
    'regenerated_secret' => '',
]);

mount(function (int $api_client) {
    $this->client = ApiClient::findOrFail($api_client);
    $this->name = $this->client->name;
    $this->is_active = $this->client->is_active;
    $this->rate_limit = $this->client->rate_limit;
    $this->allowed_ips = $this->client->allowed_ips ? implode("\n", $this->client->allowed_ips) : '';
});

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'is_active' => 'boolean',
        'rate_limit' => 'required|integer|min:1|max:10000',
        'allowed_ips' => 'nullable|string',
    ]);

    $ips = $this->allowed_ips
        ? array_map('trim', explode("\n", $this->allowed_ips))
        : null;

    $this->client->update([
        'name' => $this->name,
        'is_active' => $this->is_active,
        'rate_limit' => $this->rate_limit,
        'allowed_ips' => $ips,
    ]);

    Flux::toast(duration: 1500, variant: 'success', text: __('API client updated successfully.'));

    $this->redirect(route('api-clients.index'));
};

$regenerateSecret = function () {
    $this->client->regenerateSecret();
    $this->client->refresh();
    $this->regenerated_secret = $this->client->api_secret;
};

?>

<x-layouts::app :title="__('Edit API Client')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('api-clients.index') }}">{{ __('API Clients') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Edit API Client') }}</flux:heading>
                <flux:subheading>{{ __('Update :name', ['name' => $client->name]) }}</flux:subheading>
            </div>
        </div>

        <div
            class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" :label="__('Client Name')" required autofocus />

                <div class="space-y-1">
                    <flux:text size="sm" class="font-semibold">{{ __('API Key') }}</flux:text>
                    <code
                        class="block text-sm font-mono bg-zinc-100 dark:bg-zinc-700 px-3 py-2 rounded">{{ $client->api_key }}</code>
                </div>

                <flux:input wire:model="rate_limit" type="number" min="1" max="10000"
                    :label="__('Rate Limit (requests per minute)')" required />

                <flux:textarea wire:model="allowed_ips" :label="__('Allowed IPs')" rows="3"
                    :placeholder="__('One IP or CIDR per line. Leave empty to allow all.')" />

                <div>
                    <flux:checkbox wire:model="is_active" :label="__('Active')" />
                </div>

                @if ($regenerated_secret)
                    <div class="rounded-lg border border-amber-200 dark:border-amber-700 p-4 bg-amber-50 dark:bg-amber-900/20">
                        <flux:heading size="sm" class="text-amber-800 dark:text-amber-200">{{ __('New API Secret') }}</flux:heading>
                        <flux:text size="sm" class="mt-1 text-amber-700 dark:text-amber-300">
                            {{ __('Save this now — it won\'t be shown again. Old clients must update their secret.') }}
                        </flux:text>
                        <code class="block mt-2 text-sm font-mono bg-white dark:bg-zinc-800 px-3 py-2 rounded border border-amber-300 dark:border-amber-600 select-all">{{ $regenerated_secret }}</code>
                    </div>
                @endif

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('api-clients.index') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                    <flux:button wire:click="regenerateSecret" variant="warning">{{ __('Regenerate Secret') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Update API Client') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
    @volt
</x-layouts::app>
