<?php

namespace App\Exceptions;

use Exception;

class DeviceLimitExceededException extends Exception
{
    public function __construct(string $message = 'Batas perangkat tercapai')
    {
        parent::__construct($message, 429);
    }
}
