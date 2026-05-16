<?php

namespace App\Listeners;

use App\Events\ActivationApproved;
use App\Events\ActivationRejected;
use App\Events\DeviceDeactivated;
use App\Events\DeviceRegistered;
use App\Events\LicenseCreated;
use App\Events\LicenseRestored;
use App\Events\LicenseRevoked;
use App\Events\LicenseSuspended;
use App\Services\WebhookService;
use Illuminate\Events\Dispatcher;

class WebhookSubscriber
{
    public function __construct(
        protected WebhookService $webhookService,
    ) {}

    public function onLicenseCreated(LicenseCreated $event): void
    {
        $this->webhookService->dispatch('license.created', [
            'event' => 'license.created',
            'license_key' => $event->license->key,
            'status' => $event->license->status->value,
            'product' => $event->license->product?->name,
            'expires_at' => $event->license->expires_at?->toIso8601String(),
        ]);
    }

    public function onLicenseSuspended(LicenseSuspended $event): void
    {
        $this->webhookService->dispatch('license.suspended', [
            'event' => 'license.suspended',
            'license_key' => $event->license->key,
            'status' => $event->license->status->value,
        ]);
    }

    public function onLicenseRevoked(LicenseRevoked $event): void
    {
        $this->webhookService->dispatch('license.revoked', [
            'event' => 'license.revoked',
            'license_key' => $event->license->key,
            'status' => $event->license->status->value,
        ]);
    }

    public function onLicenseRestored(LicenseRestored $event): void
    {
        $this->webhookService->dispatch('license.restored', [
            'event' => 'license.restored',
            'license_key' => $event->license->key,
            'status' => $event->license->status->value,
        ]);
    }

    public function onDeviceRegistered(DeviceRegistered $event): void
    {
        $this->webhookService->dispatch('device.registered', [
            'event' => 'device.registered',
            'license_key' => $event->device->license?->key,
            'device_fingerprint' => $event->device->fingerprint,
            'platform' => $event->device->platform,
        ]);
    }

    public function onDeviceDeactivated(DeviceDeactivated $event): void
    {
        $this->webhookService->dispatch('device.deactivated', [
            'event' => 'device.deactivated',
            'license_key' => $event->device->license?->key,
            'device_fingerprint' => $event->device->fingerprint,
        ]);
    }

    public function onActivationApproved(ActivationApproved $event): void
    {
        $this->webhookService->dispatch('activation.approved', [
            'event' => 'activation.approved',
            'license_key' => $event->activationRequest->license?->key,
            'device_fingerprint' => $event->activationRequest->device?->fingerprint,
        ]);
    }

    public function onActivationRejected(ActivationRejected $event): void
    {
        $this->webhookService->dispatch('activation.rejected', [
            'event' => 'activation.rejected',
            'license_key' => $event->activationRequest->license?->key,
            'device_fingerprint' => $event->activationRequest->device?->fingerprint,
            'reason' => $event->reason,
        ]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            LicenseCreated::class => 'onLicenseCreated',
            LicenseSuspended::class => 'onLicenseSuspended',
            LicenseRevoked::class => 'onLicenseRevoked',
            LicenseRestored::class => 'onLicenseRestored',
            DeviceRegistered::class => 'onDeviceRegistered',
            DeviceDeactivated::class => 'onDeviceDeactivated',
            ActivationApproved::class => 'onActivationApproved',
            ActivationRejected::class => 'onActivationRejected',
        ];
    }
}
