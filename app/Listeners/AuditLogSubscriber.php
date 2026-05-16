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
use App\Models\AuditLog;
use App\Models\License;
use Illuminate\Events\Dispatcher;

class AuditLogSubscriber
{
    public function onLicenseCreated(LicenseCreated $event): void
    {
        AuditLog::create([
            'action' => 'created',
            'entity_type' => License::class,
            'entity_id' => $event->license->id,
            'new_values' => $event->license->toArray(),
            'created_at' => now(),
        ]);
    }

    public function onLicenseSuspended(LicenseSuspended $event): void
    {
        AuditLog::create([
            'action' => 'suspended',
            'entity_type' => License::class,
            'entity_id' => $event->license->id,
            'user_id' => $event->userId,
            'old_values' => ['status' => $event->license->getOriginal('status')],
            'new_values' => ['status' => $event->license->status],
            'created_at' => now(),
        ]);
    }

    public function onLicenseRevoked(LicenseRevoked $event): void
    {
        AuditLog::create([
            'action' => 'revoked',
            'entity_type' => License::class,
            'entity_id' => $event->license->id,
            'user_id' => $event->userId,
            'old_values' => ['status' => $event->license->getOriginal('status')],
            'new_values' => ['status' => $event->license->status],
            'created_at' => now(),
        ]);
    }

    public function onLicenseRestored(LicenseRestored $event): void
    {
        AuditLog::create([
            'action' => 'restored',
            'entity_type' => License::class,
            'entity_id' => $event->license->id,
            'user_id' => $event->userId,
            'old_values' => ['status' => $event->license->getOriginal('status')],
            'new_values' => ['status' => $event->license->status],
            'created_at' => now(),
        ]);
    }

    public function onDeviceRegistered(DeviceRegistered $event): void
    {
        AuditLog::create([
            'action' => 'device_registered',
            'entity_type' => License::class,
            'entity_id' => $event->device->license_id,
            'new_values' => ['device_id' => $event->device->id],
            'created_at' => now(),
        ]);
    }

    public function onDeviceDeactivated(DeviceDeactivated $event): void
    {
        AuditLog::create([
            'action' => 'device_deactivated',
            'entity_type' => License::class,
            'entity_id' => $event->device->license_id,
            'new_values' => ['device_id' => $event->device->id],
            'created_at' => now(),
        ]);
    }

    public function onActivationApproved(ActivationApproved $event): void
    {
        AuditLog::create([
            'action' => 'activation_approved',
            'entity_type' => License::class,
            'entity_id' => $event->activationRequest->license_id,
            'user_id' => $event->userId,
            'new_values' => [
                'request_id' => $event->activationRequest->id,
                'device_id' => $event->activationRequest->device_id,
            ],
            'created_at' => now(),
        ]);
    }

    public function onActivationRejected(ActivationRejected $event): void
    {
        AuditLog::create([
            'action' => 'activation_rejected',
            'entity_type' => License::class,
            'entity_id' => $event->activationRequest->license_id,
            'user_id' => $event->userId,
            'new_values' => [
                'request_id' => $event->activationRequest->id,
                'device_id' => $event->activationRequest->device_id,
                'reason' => $event->reason,
            ],
            'created_at' => now(),
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
