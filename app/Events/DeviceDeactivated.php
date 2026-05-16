<?php

namespace App\Events;

use App\Models\Device;

class DeviceDeactivated
{
    public function __construct(public Device $device) {}
}
