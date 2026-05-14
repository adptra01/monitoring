<?php

namespace App\Services;

use Illuminate\Support\Str;

class LicenseKeyService
{
    public function generate(): string
    {
        return strtoupper(
            Str::random(4).'-'.
            Str::random(4).'-'.
            Str::random(4).'-'.
            Str::random(4)
        );
    }

    public function validateFormat(string $key): bool
    {
        return preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key) === 1;
    }

    public function mask(string $key): string
    {
        $parts = explode('-', $key);
        if (count($parts) !== 4) {
            return '****-****-****-****';
        }

        return $parts[0].'-****-****-'.$parts[3];
    }
}
