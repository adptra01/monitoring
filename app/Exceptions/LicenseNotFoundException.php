<?php

namespace App\Exceptions;

use Exception;

class LicenseNotFoundException extends Exception
{
    public function __construct(string $message = 'Kunci lisensi tidak valid')
    {
        parent::__construct($message, 404);
    }
}
