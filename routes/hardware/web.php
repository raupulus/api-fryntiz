<?php
/*
 * Archivo de rutas para la web de estación meteorológica accesible desde el
 * sufijo /hardware/*
 */

use App\Http\Controllers\Hardware\EnergyController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/'], function () {

    ## Muestra un listado del hardware dado de alta en el sistema
    /*
    Route::get('/', [])
        ->name('hardware.index');
    */
});

Route::group(['prefix' => '/energy'], function () {
    ## Muestra un resumen de los datos de energía generados y consumidos
    Route::get('/', [EnergyController::class, 'index'])
        ->name('hardware.energy.index');
});
