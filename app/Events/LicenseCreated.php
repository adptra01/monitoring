<?php

namespace App\Events;

use App\Models\License;

class LicenseCreated
{
    public function __construct(public License $license) {}
}
