<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\DeactivateController;
use App\Http\Controllers\Api\ValidationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['api-client', 'throttle:api-client'])->group(function () {
    Route::post('/activate', [ActivationController::class, 'activate'])
        ->middleware('brute-force');
    Route::get('/verify/{key}/{fingerprint}', [ActivationController::class, 'verify'])
        ->where('key', '[A-Z0-9-]+');
    Route::get('/status/{key}/{fingerprint}', [ActivationController::class, 'status'])
        ->where('key', '[A-Z0-9-]+');

    Route::post('/validate', [ValidationController::class, 'validate']);
    Route::post('/check-update', CheckUpdateController::class);
    Route::post('/deactivate', DeactivateController::class);
});
