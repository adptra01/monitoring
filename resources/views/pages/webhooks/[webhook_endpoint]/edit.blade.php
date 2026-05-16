<?php

use App\Models\WebhookEndpoint;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state, mount};

name('webhooks.edit');
middleware('check.admin');

state([
    'endpoint' => null,
    'url' => '',
    'events' => [],
    'is_active' => true,
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

mount(function (int $webhook_endpoint) {
    $this->endpoint = WebhookEndpoint::findOrFail($webhook_endpoint);
    $this->url = $this->endpoint->url;
    $this->events = $this->endpoint->events ?? [];
    $this->is_active = $this->endpoint->is_active;
});

$save = function () {
    $validEvents = implode(',', $this->availableEvents);

    $this->validate([
        'url' => 'required|url|max:500',
        'events' => 'required|array|min:1',
        'events.*' => 'in:'.$validEvents,
        'is_active' => 'boolean',
    ]);

    $this->endpoint->update([
        'url' => $this->url,
        'events' => $this->events,
        'is_active' => $this->is_active,
    ]);

    Flux::toast(duration: 1500, variant: 'success', text: __('Webhook endpoint updated successfully.'));

    $this->redirect(route('webhooks.index'));
};

?>

<x-layouts::app :title="__('Edit Webhook')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('webhooks.index') }}">{{ __('Webhooks') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Edit Webhook Endpoint') }}</flux:heading>
                <flux:subheading>{{ __('Update :url', ['url' => $endpoint->url]) }}</flux:subheading>
            </div>
        </div>

        <div
            class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="url" type="url" :label="__('Webhook URL')" required autofocus />

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
                    <flux:button type="submit" variant="primary">{{ __('Update Webhook') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endvolt
</x-layouts::app>
