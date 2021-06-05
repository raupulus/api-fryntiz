<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\AirFlight\AirFlightController@index')
    ->name('airflight.index');

Route::get('/get/aircrafts/json', 'App\Http\Controllers\Api\AirFlight\AirFlightController@getAircraftjson');
