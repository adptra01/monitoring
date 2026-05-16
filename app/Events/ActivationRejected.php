<?php

namespace App\Events;

use App\Models\ActivationRequest;

class ActivationRejected
{
    public function __construct(
        public ActivationRequest $activationRequest,
        public string $reason = '',
        public ?string $userId = null,
    ) {}
}
