<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Energy\V2\EnergyReadingController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Energía
|--------------------------------------------------------------------------
|
| Módulo propio, al mismo nivel que la estación meteorológica, KeyCounter,
| SmartPlant o AirFlight. Sus lecturas cuelgan de un `hardware_device_id`.
|
| Endpoints unificados V2:
|   POST /energy/readings   Ingesta universal de telemetría (3 bloques: generación, consumo, batería).
|   GET  /energy/readings   Consulta paginada de lecturas (admite filtros ?role=..., ?channel=..., etc.).
|
*/

Route::prefix('energy')->group(function () {
    // # Lecturas: exigen `energy:read`.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_READ, 'throttle:api'])->group(function () {
        Route::get('/readings', [EnergyReadingController::class, 'index'])->name('api.v2.energy.readings.index');
    });

    // # Subidas IoT: exigen `energy:write`.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/readings', [EnergyReadingController::class, 'store'])->name('api.v2.energy.readings.store');
    });
});
