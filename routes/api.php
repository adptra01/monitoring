<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\ValidationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/activate', [ActivationController::class, 'activate']);
    Route::get('/verify/{key}/{fingerprint}', [ActivationController::class, 'verify'])
        ->where('key', '[A-Z0-9-]+');
    Route::get('/status/{key}/{fingerprint}', [ActivationController::class, 'status'])
        ->where('key', '[A-Z0-9-]+');

    Route::post('/validate', [ValidationController::class, 'validate']);
});
