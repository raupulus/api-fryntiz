<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WeatherStation\V2\AirQualityController;
use App\Http\Controllers\Api\WeatherStation\V2\Eco2Controller;
use App\Http\Controllers\Api\WeatherStation\V2\GenericController;
use App\Http\Controllers\Api\WeatherStation\V2\HumidityController;
use App\Http\Controllers\Api\WeatherStation\V2\LightController;
use App\Http\Controllers\Api\WeatherStation\V2\LightningController;
use App\Http\Controllers\Api\WeatherStation\V2\PressureController;
use App\Http\Controllers\Api\WeatherStation\V2\RainController;
use App\Http\Controllers\Api\WeatherStation\V2\StationController;
use App\Http\Controllers\Api\WeatherStation\V2\TemperatureController;
use App\Http\Controllers\Api\WeatherStation\V2\TvocController;
use App\Http\Controllers\Api\WeatherStation\V2\WindController;
use App\Http\Controllers\Api\WeatherStation\V2\WindDirectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Weather Station
|--------------------------------------------------------------------------
*/

Route::prefix('weatherstation')->group(function () {
    // # Estaciones (datos formateados, selección de sensores con ?sensors=)
    // Una estación por id (id opcional → primera de exterior)
    Route::get('/station/{station?}', [StationController::class, 'show'])
        ->whereNumber('station')
        ->name('api.v2.weatherstation.station.show');
    // Todas las estaciones de una zona (?location_type=indoor|outdoor)
    Route::get('/zone/{zone}', [StationController::class, 'zone'])
        ->name('api.v2.weatherstation.zone.show');

    // # Lecturas públicas (con filtro opcional ?from=&to=)
    Route::get('/temperature', [TemperatureController::class, 'index'])->name('api.v2.weatherstation.temperature.index');
    Route::get('/humidity', [HumidityController::class, 'index'])->name('api.v2.weatherstation.humidity.index');
    Route::get('/pressure', [PressureController::class, 'index'])->name('api.v2.weatherstation.pressure.index');
    Route::get('/light', [LightController::class, 'index'])->name('api.v2.weatherstation.light.index');
    Route::get('/wind', [WindController::class, 'index'])->name('api.v2.weatherstation.wind.index');
    Route::get('/wind-direction', [WindDirectionController::class, 'index'])->name('api.v2.weatherstation.wind_direction.index');
    Route::get('/rain', [RainController::class, 'index'])->name('api.v2.weatherstation.rain.index');
    Route::get('/eco2', [Eco2Controller::class, 'index'])->name('api.v2.weatherstation.eco2.index');
    Route::get('/tvoc', [TvocController::class, 'index'])->name('api.v2.weatherstation.tvoc.index');
    Route::get('/air-quality', [AirQualityController::class, 'index'])->name('api.v2.weatherstation.air_quality.index');
    Route::get('/lightning', [LightningController::class, 'index'])->name('api.v2.weatherstation.lightning.index');

    // # Escrituras IoT: token Sanctum por dispositivo con ability "weatherstation:write" + rate-limit.
    Route::middleware(['auth:sanctum', 'ability:weatherstation:write', 'throttle:api-store'])->group(function () {
        Route::post('/generic/store', [GenericController::class, 'store'])->name('api.v2.weatherstation.generic.store');
        Route::post('/temperature/store', [TemperatureController::class, 'store'])->name('api.v2.weatherstation.temperature.store');
        Route::post('/humidity/store', [HumidityController::class, 'store'])->name('api.v2.weatherstation.humidity.store');
        Route::post('/pressure/store', [PressureController::class, 'store'])->name('api.v2.weatherstation.pressure.store');
        Route::post('/light/store', [LightController::class, 'store'])->name('api.v2.weatherstation.light.store');
        Route::post('/wind/store', [WindController::class, 'store'])->name('api.v2.weatherstation.wind.store');
        Route::post('/wind-direction/store', [WindDirectionController::class, 'store'])->name('api.v2.weatherstation.wind_direction.store');
        Route::post('/rain/store', [RainController::class, 'store'])->name('api.v2.weatherstation.rain.store');
        Route::post('/eco2/store', [Eco2Controller::class, 'store'])->name('api.v2.weatherstation.eco2.store');
        Route::post('/tvoc/store', [TvocController::class, 'store'])->name('api.v2.weatherstation.tvoc.store');
        Route::post('/air-quality/store', [AirQualityController::class, 'store'])->name('api.v2.weatherstation.air_quality.store');
        Route::post('/lightning/store', [LightningController::class, 'store'])->name('api.v2.weatherstation.lightning.store');
    });
});
