<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Hardware\V2\DeviceStatusController;
use App\Http\Controllers\Api\Hardware\V2\EnergyMonitorController;
use App\Http\Controllers\Api\Hardware\V2\HardwareDeviceController;
use App\Http\Controllers\Api\Hardware\V2\SolarChargeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Hardware
|--------------------------------------------------------------------------
*/

Route::prefix('hardware')->group(function () {
    // # Lecturas (token autenticado)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/device/{id}', [HardwareDeviceController::class, 'show'])->name('api.v2.hardware.device.show');
        Route::get('/computers', [HardwareDeviceController::class, 'computers'])->name('api.v2.hardware.computers');
    });

    // # Escrituras IoT: token por dispositivo con ability "hardware:write" + rate-limit.
    Route::middleware(['auth:sanctum', 'ability:hardware:write', 'throttle:api-store'])->group(function () {
        Route::post('/energy', [EnergyMonitorController::class, 'store'])->name('api.v2.hardware.energy.store');
        Route::post('/solar-charge', [SolarChargeController::class, 'store'])->name('api.v2.hardware.solar_charge.store');
        Route::post('/device-status', [DeviceStatusController::class, 'store'])->name('api.v2.hardware.device_status.store');
    });
});
