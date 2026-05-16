<?php

namespace App\Events;

use App\Models\License;

class LicenseSuspended
{
    public function __construct(
        public License $license,
        public ?string $userId = null,
    ) {}
}
