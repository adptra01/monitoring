<?php

namespace App\Events;

use App\Models\ActivationRequest;

class ActivationApproved
{
    public function __construct(
        public ActivationRequest $activationRequest,
        public ?string $userId = null,
    ) {}
}
