<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Hardware\V2\EnergyMonitorController;
use App\Http\Controllers\Api\Hardware\V2\HardwareDeviceController;
use App\Http\Controllers\Api\Hardware\V2\SolarReadingController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Hardware
|--------------------------------------------------------------------------
|
| `GET /hardware/computers` era una ruta propia para lo que es un filtro de la
| colección: `GET /hardware/devices?type=laptop`.
|
| Y el estado del dispositivo pasa de `POST /hardware/device-status` a
| `PUT /hardware/devices/{device}/status`: es «el último estado conocido», se
| sobrescribe, y repetir la petición tiene que dejar el sistema igual.
|
*/

Route::prefix('hardware')->group(function () {
    // # Inventario: exige la ability "hardware:read".
    //
    // Sin ella, el token de una estación meteorológica leía el inventario
    // completo de todos los usuarios, con números de serie, iterando el id
    // (auditoría A3). La ability estaba declarada en el catálogo y no la pedía
    // ninguna ruta (A9).
    // AR-A01: el `throttle:api` faltaba. Estar autenticado acota el daño pero
    // no lo elimina: un token filtrado —el escenario del que se defiende todo
    // el diseño de abilities— podía iterar el inventario a la velocidad que
    // diera el servidor.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWARE_READ, 'throttle:api'])->group(function () {
        Route::get('/devices', [HardwareDeviceController::class, 'index'])->name('api.v2.hardware.devices.index');
        Route::get('/devices/{device}', [HardwareDeviceController::class, 'show'])
            ->whereNumber('device')
            ->name('api.v2.hardware.devices.show');
    });

    // # Estado del aparato: token por dispositivo con ability "hardware:write".
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWARE_WRITE, 'throttle:api-store'])->group(function () {
        Route::put('/devices/{device}/status', [HardwareDeviceController::class, 'updateStatus'])
            ->whereNumber('device')
            ->name('api.v2.hardware.devices.status.update');
    });

    // # Energía: módulo aparte, con sus propias abilities.
    //
    // Las lecturas de energía —de un controlador solar o de una pinza de
    // consumo— iban con `hardware:write` hasta el 2026-09-06. Eso metía dos
    // permisos distintos en la misma casilla: el token de un contador de
    // consumo, que sólo tiene que mandar vatios, también podía reescribir el
    // último estado conocido del aparato. Un cacharro de energía lleva ahora
    // `hardwareenergy:write` y nada más.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWAREENERGY_READ, 'throttle:api'])->group(function () {
        Route::get('/energy-readings', [EnergyMonitorController::class, 'index'])->name('api.v2.hardware.energy_readings.index');
        Route::get('/solar-readings', [SolarReadingController::class, 'index'])->name('api.v2.hardware.solar_readings.index');
    });

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWAREENERGY_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/energy-readings', [EnergyMonitorController::class, 'store'])->name('api.v2.hardware.energy_readings.store');
        Route::post('/solar-readings', [SolarReadingController::class, 'store'])->name('api.v2.hardware.solar_readings.store');
    });
});
