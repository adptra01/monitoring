<?php

namespace App\Enums;

enum AuditAction: string
{
    case LicenseCreated = 'license.created';
    case LicenseActivated = 'license.activated';
    case LicenseValidated = 'license.validated';
    case LicenseRevoked = 'license.revoked';
    case LicenseSuspended = 'license.suspended';
    case LicenseExpired = 'license.expired';
    case DeviceBound = 'device.bound';
    case ActivationApproved = 'activation.approved';
    case ActivationRejected = 'activation.rejected';
    case ActivationRequested = 'activation.requested';
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionRenewed = 'subscription.renewed';
    case SubscriptionExpired = 'subscription.expired';
    case DevicesForceReset = 'devices.force_reset';
}
