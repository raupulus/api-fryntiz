<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Hardware\V2\HardwareDeviceController;
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
| **Este módulo es el aparato y nada más**: inventario y salud —IP, uptime, CPU,
| RAM, discos, temperatura, batería—. Lo que el aparato *mide* vive en el módulo
| de su materia, todos ellos colgados de un `hardware_device_id`: energía en
| `routes/energy/v2.php`, sensores en `routes/weather_station/v2.php`,
| pulsaciones en `routes/keycounter/v2.php`, plantas y vuelos en los suyos.
| Las lecturas de energía estuvieron aquí hasta el 2026-09-06 y no tenía
| sentido: ningún otro módulo estaba dentro de hardware.
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
});
