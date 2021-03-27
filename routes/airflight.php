<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

######################################################
##                    Pública
######################################################
Route::get('/', '\App\Api\AirFlight\Controllers\AirFlightController@index')
    ->name('airflight.index');

Route::get('/get/aircrafts/json', '\App\Api\AirFlight\Controllers\AirFlightController@getAircraftjson');

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:api']
], function () {
    ##
    Route::post('/register/add', '\App\Api\AirFlight\Controllers\AirFlightController@add');

    ## Añade entradas por lotes json
    Route::post('/register/add-json', '\App\Api\AirFlight\Controllers\AirFlightController@addJson');
});

