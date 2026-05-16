<?php

use App\Models\WebhookEndpoint;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state};

name('webhooks.create');
middleware('check.admin');

state([
    'url' => '',
    'events' => [],
    'is_active' => true,
    'generated_secret' => '',
    'saved' => false,
]);

$availableEvents = computed(function () {
    return [
        'license.created',
        'license.suspended',
        'license.revoked',
        'license.restored',
        'device.registered',
        'device.deactivated',
        'activation.approved',
        'activation.rejected',
    ];
});

$save = function () {
    $validEvents = implode(',', $this->availableEvents);

    $this->validate([
        'url' => 'required|url|max:500',
        'events' => 'required|array|min:1',
        'events.*' => 'in:'.$validEvents,
        'is_active' => 'boolean',
    ]);

    $secret = WebhookEndpoint::generateSecret();

    WebhookEndpoint::create([
        'url' => $this->url,
        'events' => $this->events,
        'secret' => $secret,
        'is_active' => $this->is_active,
    ]);

    $this->generated_secret = $secret;
    $this->saved = true;

    Flux::toast(duration: 5000, variant: 'success', text: __('Webhook endpoint created successfully.'));
};

$done = function () {
    $this->redirect(route('webhooks.index'));
};

?>

<x-layouts::app :title="__('Create Webhook')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('webhooks.index') }}">{{ __('Webhooks') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Create Webhook Endpoint') }}</flux:heading>
                <flux:subheading>{{ __('Configure a URL to receive event notifications') }}</flux:subheading>
            </div>
        </div>

        @if ($saved)
            <div
                class="max-w-2xl rounded-xl border border-amber-200 dark:border-amber-700 p-6 bg-amber-50 dark:bg-amber-900/20">
                <flux:heading size="lg" class="text-amber-800 dark:text-amber-200">{{ __('Webhook Secret') }}</flux:heading>
                <flux:subheading class="mt-1 text-amber-700 dark:text-amber-300">
                    {{ __('Copy this secret now. It will not be shown again. Use it to verify incoming webhook signatures.') }}
                </flux:subheading>

                <div class="mt-4">
                    <flux:text size="sm" class="font-semibold">{{ __('Secret') }}:</flux:text>
                    <code
                        class="block mt-1 text-sm font-mono bg-white dark:bg-zinc-800 px-3 py-2 rounded border border-amber-300 dark:border-amber-600 select-all">{{ $generated_secret }}</code>
                </div>

                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="done">{{ __('Done, take me back') }}</flux:button>
                </div>
            </div>
        @else
            <div
                class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="url" type="url" :label="__('Webhook URL')" required autofocus
                        placeholder="https://example.com/webhooks/license-events" />

                    <flux:field>
                        <flux:label>{{ __('Events') }}</flux:label>
                        <flux:description>{{ __('Select the events to subscribe to') }}</flux:description>
                        <div class="mt-2 space-y-2">
                            @foreach ($this->availableEvents as $event)
                                <flux:checkbox wire:model="events" value="{{ $event }}" :label="$event" />
                            @endforeach
                        </div>
                        <flux:error name="events" />
                    </flux:field>

                    <div>
                        <flux:checkbox wire:model="is_active" :label="__('Active')" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('webhooks.index') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Create Webhook') }}</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    @endvolt
</x-layouts::app>
