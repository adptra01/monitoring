<?php

namespace App\Exceptions;

use Exception;

class LicenseSuspendedException extends Exception
{
    public function __construct(string $message = 'Lisensi ditangguhkan')
    {
        parent::__construct($message, 403);
    }
}
