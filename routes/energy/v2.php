<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Energy\V2\EnergyMonitorController;
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
| Estuvo dentro de `/hardware` hasta el 2026-09-06, y era una excepción sin
| motivo: ningún otro módulo vive ahí. `hardware` es el dispositivo en sí
| —inventario y salud: IP, uptime, CPU, RAM, discos, temperatura—, y lo que el
| dispositivo *mide* es cosa de su módulo.
|
| Dos aparatos distintos, dos endpoints:
|
|   POST /energy/solar-readings   un controlador solar (Renogy Rover y demás):
|                                 una fila por lectura, con generación, batería,
|                                 estadísticas del día y acumulados.
|   POST /energy/readings         un monitor de consumo de varios canales: cada
|                                 canal se asigna a su elemento de
|                                 `hardware_energy` por `sensor_position`.
|
*/

Route::prefix('energy')->group(function () {
    // # Lecturas: exigen `energy:read`.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_READ, 'throttle:api'])->group(function () {
        Route::get('/readings', [EnergyMonitorController::class, 'index'])->name('api.v2.energy.readings.index');
        Route::get('/solar-readings', [SolarReadingController::class, 'index'])->name('api.v2.energy.solar_readings.index');
    });

    // # Subidas IoT: exigen `energy:write`.
    //
    // Es la ability de un controlador solar o de un contador de consumo. No
    // incluye el estado del propio aparato (`PUT /hardware/devices/{id}/status`,
    // ability `hardware:write`): son dos permisos y se conceden por separado.
    // Aun así, las dos subidas admiten `hardware_device_info` para no obligar a
    // una segunda petición sólo para decir la RAM o el uptime.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::ENERGY_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/readings', [EnergyMonitorController::class, 'store'])->name('api.v2.energy.readings.store');
        Route::post('/solar-readings', [SolarReadingController::class, 'store'])->name('api.v2.energy.solar_readings.store');
    });
});
