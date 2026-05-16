<?php

namespace App\Exceptions;

use Exception;

class LicenseExpiredException extends Exception
{
    public function __construct(string $message = 'Lisensi telah kedaluwarsa')
    {
        parent::__construct($message, 403);
    }
}
