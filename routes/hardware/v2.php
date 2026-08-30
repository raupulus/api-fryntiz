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
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWARE_READ])->group(function () {
        Route::get('/devices', [HardwareDeviceController::class, 'index'])->name('api.v2.hardware.devices.index');
        Route::get('/devices/{device}', [HardwareDeviceController::class, 'show'])
            ->whereNumber('device')
            ->name('api.v2.hardware.devices.show');
    });

    // # Escrituras IoT: token por dispositivo con ability "hardware:write".
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::HARDWARE_WRITE, 'throttle:api-store'])->group(function () {
        Route::put('/devices/{device}/status', [HardwareDeviceController::class, 'updateStatus'])
            ->whereNumber('device')
            ->name('api.v2.hardware.devices.status.update');

        Route::post('/energy-readings', [EnergyMonitorController::class, 'store'])->name('api.v2.hardware.energy_readings.store');
        Route::post('/solar-readings', [SolarReadingController::class, 'store'])->name('api.v2.hardware.solar_readings.store');
    });
});
