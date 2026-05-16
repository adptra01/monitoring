<?php

use App\Http\Controllers\Api\ActivationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckUpdateController;
use App\Http\Controllers\Api\DeactivateController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\ValidationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth', AuthController::class);

    Route::get('/health', HealthController::class);

    Route::get('/metrics', [MetricsController::class, 'prometheus']);
});

Route::prefix('v1')->middleware(['api-client', 'ip-whitelist', 'throttle:api-client'])->group(function () {
    Route::post('/activate', [ActivationController::class, 'activate'])
        ->middleware('brute-force');
    Route::get('/verify/{key}/{fingerprint}', [ActivationController::class, 'verify'])
        ->where('key', '[A-Z0-9-]+');
    Route::get('/status/{key}/{fingerprint}', [ActivationController::class, 'status'])
        ->where('key', '[A-Z0-9-]+');

    Route::post('/validate', [ValidationController::class, 'validate']);
    Route::post('/check-update', CheckUpdateController::class);
    Route::post('/deactivate', DeactivateController::class);

    Route::post('/token/refresh', [TokenController::class, 'refresh']);
});
