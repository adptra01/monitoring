<?php

namespace App\Services;

use Illuminate\Support\Str;

class LicenseKeyService
{
    public function generate(): string
    {
        $segment1 = strtoupper(Str::random(8));
        $segment2 = strtoupper(Str::random(8));

        return "LIC-{$segment1}-{$segment2}";
    }

    public function validateFormat(string $key): bool
    {
        return (bool) preg_match('/^LIC-[A-Z0-9]{8}-[A-Z0-9]{8}$/', $key);
    }
}
