<?php

namespace App\Exceptions;

use Exception;

class InvalidActivationCodeException extends Exception
{
    public function __construct(string $message = 'Kode aktivasi tidak valid')
    {
        parent::__construct($message, 400);
    }
}
