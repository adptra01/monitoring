<?php

namespace App\Events;

use App\Models\License;

class LicenseRevoked
{
    public function __construct(
        public License $license,
        public ?string $userId = null,
    ) {}
}
