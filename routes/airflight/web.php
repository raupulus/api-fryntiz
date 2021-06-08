<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

use Illuminate\Support\Facades\Route;

Route::get('/', 'AirFlight\AirFlightController@index')
    ->name('airflight.index');
