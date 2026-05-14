<?php

use App\Http\Controllers\Api\LicenseActivationController;
use App\Http\Controllers\Api\LicenseUpdateController;
use App\Http\Controllers\Api\LicenseValidationController;
use Illuminate\Support\Facades\Route;

Route::post('/license/validate', LicenseValidationController::class)
    ->middleware('throttle:60,1');

Route::post('/license/activate', LicenseActivationController::class)
    ->middleware('throttle:30,1');

Route::post('/license/check-update', LicenseUpdateController::class)
    ->middleware('throttle:30,1');
