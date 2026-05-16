<?php

namespace App\Exceptions;

use Exception;

class DeviceNotRegisteredException extends Exception
{
    public function __construct(string $message = 'Perangkat tidak terdaftar')
    {
        parent::__construct($message, 403);
    }
}
