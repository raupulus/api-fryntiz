<?php

use App\Http\Controllers\Api\AirFlight\V2\AirFlightController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — AirFlight
|--------------------------------------------------------------------------
*/

Route::prefix('airflight')->group(function () {
    Route::get('/aircrafts', [AirFlightController::class, 'aircrafts'])->name('api.v2.airflight.aircrafts');
    Route::get('/history', [AirFlightController::class, 'history'])->name('api.v2.airflight.history');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/register', [AirFlightController::class, 'store'])->middleware('throttle:api-store')->name('api.v2.airflight.store');
        Route::post('/register/batch', [AirFlightController::class, 'storeBatch'])->middleware('throttle:api-store-batch')->name('api.v2.airflight.store_batch');
    });
});
