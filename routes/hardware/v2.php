<?php

use App\Http\Controllers\Api\Hardware\V2\EnergyMonitorController;
use App\Http\Controllers\Api\Hardware\V2\HardwareDeviceController;
use App\Http\Controllers\Api\Hardware\V2\SolarChargeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Hardware
|--------------------------------------------------------------------------
*/

Route::prefix('hardware')->middleware(['auth:sanctum', 'throttle:api-store'])->group(function () {
    Route::get('/device/{id}', [HardwareDeviceController::class, 'show'])->name('api.v2.hardware.device.show');
    Route::get('/computers', [HardwareDeviceController::class, 'computers'])->name('api.v2.hardware.computers');
    Route::post('/energy', [EnergyMonitorController::class, 'store'])->name('api.v2.hardware.energy.store');
    Route::post('/solar-charge', [SolarChargeController::class, 'store'])->name('api.v2.hardware.solar_charge.store');
});
