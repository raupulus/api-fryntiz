<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

######################################################
##                    Pública
######################################################
Route::get('/', 'AirFlight\AirFlightController@index')->name('airflight.index');

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:api']
], function () {
    ##
    Route::post('/register/add', 'AirFlight\AirFlightController@add');

    ##
    Route::post('/register/add-json', 'AirFlight\AirFlightController@addJson');
});

