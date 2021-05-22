<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################

Route::get('/', 'App\Http\Controllers\AirFlight\AirFlightController@index')
    ->name('airflight.index');

Route::get('/get/aircrafts/json', 'App\Http\Controllers\Api\AirFlight\AirFlightController@getAircraftjson');

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:sanctum']
], function () {
    ##
    Route::post('/register/add', 'Api\AirFlight\AirFlightController@add');

    ## Añade entradas por lotes json
    Route::post('/register/add-json', 'Api\AirFlight\AirFlightController@addJson');
});

