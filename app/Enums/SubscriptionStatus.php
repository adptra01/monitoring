<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Trialing = 'trialing';
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PastDue => 'Past Due',
            self::Canceled => 'Canceled',
            self::Trialing => 'Trialing',
            self::Incomplete => 'Incomplete',
            self::IncompleteExpired => 'Incomplete Expired',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trialing]);
    }
}
