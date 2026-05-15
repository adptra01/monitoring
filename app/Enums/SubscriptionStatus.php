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
            self::Active => 'Aktif',
            self::PastDue => 'Jatuh Tempo',
            self::Canceled => 'Dibatalkan',
            self::Trialing => 'Masa Percobaan',
            self::Incomplete => 'Tidak Lengkap',
            self::IncompleteExpired => 'Tidak Lengkap Kedaluwarsa',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trialing]);
    }
}
