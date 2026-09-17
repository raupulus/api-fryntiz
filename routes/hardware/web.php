<?php

declare(strict_types=1);

/*
 * Archivo de rutas para la web de estación meteorológica accesible desde el
 * sufijo /hardware/*
 */

use App\Http\Controllers\Hardware\EnergyController;
use App\Http\Controllers\Hardware\HardwareDeviceController;
use Illuminate\Support\Facades\Route;

// # Muestra un listado del hardware público dado de alta en el sistema
Route::get('/', [HardwareDeviceController::class, 'index'])
    ->name('hardware.index');

Route::group(['prefix' => '/energy'], function () {
    // # Muestra un resumen de los datos de energía generados y consumidos
    Route::get('/', [EnergyController::class, 'index'])
        ->name('hardware.energy.index');
});

// # Muestra la ficha técnica detallada de un dispositivo hardware público
Route::get('/{device}', [HardwareDeviceController::class, 'show'])
    ->whereNumber('device')
    ->name('hardware.show');
