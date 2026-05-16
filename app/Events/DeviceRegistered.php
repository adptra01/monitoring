<?php

namespace App\Events;

use App\Models\Device;

class DeviceRegistered
{
    public function __construct(public Device $device) {}
}
