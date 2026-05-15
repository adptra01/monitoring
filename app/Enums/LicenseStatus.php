<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
            self::Expired => 'Kedaluwarsa',
            self::Revoked => 'Dicabut',
        };
    }

    public function isValid(): bool
    {
        return $this === self::Active;
    }
}
