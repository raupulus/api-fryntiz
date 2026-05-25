<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeatherStation\V2\GeneralController;
use App\Http\Controllers\Api\WeatherStation\V2\GenericController;
use App\Http\Controllers\Api\WeatherStation\V2\TemperatureController;
use App\Http\Controllers\Api\WeatherStation\V2\HumidityController;
use App\Http\Controllers\Api\WeatherStation\V2\PressureController;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Weather Station
|--------------------------------------------------------------------------
*/

Route::prefix('weatherstation')->group(function () {
    Route::get('/resume', [GeneralController::class, 'resume'])->name('api.v2.weatherstation.resume');
    Route::get('/temperature', [TemperatureController::class, 'index'])->name('api.v2.weatherstation.temperature.index');
    Route::get('/humidity', [HumidityController::class, 'index'])->name('api.v2.weatherstation.humidity.index');
    Route::get('/pressure', [PressureController::class, 'index'])->name('api.v2.weatherstation.pressure.index');

    Route::middleware(['auth:sanctum', 'throttle:api-store'])->group(function () {
        Route::post('/generic/store', [GenericController::class, 'store'])->name('api.v2.weatherstation.generic.store');
        Route::post('/temperature/store', [TemperatureController::class, 'store'])->name('api.v2.weatherstation.temperature.store');
        Route::post('/humidity/store', [HumidityController::class, 'store'])->name('api.v2.weatherstation.humidity.store');
        Route::post('/pressure/store', [PressureController::class, 'store'])->name('api.v2.weatherstation.pressure.store');
    });
});
