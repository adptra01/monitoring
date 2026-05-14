<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('licenses:check-expired')->daily();
Schedule::command('licenses:notify-expiring')->daily();
