<?php

namespace App\Enums;

enum LicenseMode: string
{
    case Online = 'online';
    case Offline = 'offline';
    case SemiOnline = 'semi_online';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::SemiOnline => 'Semi-Online',
        };
    }

    public function requiresActivation(): bool
    {
        return $this !== self::Offline;
    }
}