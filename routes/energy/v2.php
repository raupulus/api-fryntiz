<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Energy\V2\EnergyMonitorController;
use App\Http\Controllers\Api\Energy\V2\EnergyReadingController;
use App\Http\Controllers\Api\Energy\V2\SolarReadingController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Energía
|--------------------------------------------------------------------------
|
| Módulo propio, al mismo nivel que la estación meteorológica, KeyCounter,
| SmartPlant o AirFlight. Como todos ellos, sus lecturas cuelgan de un
| `hardware_device_id`: el aparato que las mide.
|
| Endpoints unificados V2:
|   POST /energy/readings         Ingesta universal de telemetría (bloque `energy` o `readings`).
|   GET  /energy/readings         Consulta paginada de lecturas (admite `?role=...` o `?type=...`).
|   POST /energy/solar-readings   Controlador solar legacy (con dual-write automático).
|
*/

Route::prefix('energy')->group(function () {
    // # Lecturas: exigen `energy:read`.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_READ, 'throttle:api'])->group(function () {
        Route::get('/readings', [EnergyReadingController::class, 'index'])->name('api.v2.energy.readings.index');
        Route::get('/solar-readings', [SolarReadingController::class, 'index'])->name('api.v2.energy.solar_readings.index');
    });

    // # Subidas IoT: exigen `energy:write`.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/readings', [EnergyMonitorController::class, 'store'])->name('api.v2.energy.readings.store');
        Route::post('/solar-readings', [SolarReadingController::class, 'store'])->name('api.v2.energy.solar_readings.store');
    });
});
