<?php
/*
 * Archivo de rutas para la api de registros de aviones y rutas/vuelos
 *  con él sufijo /api/airflight/v1/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
Route::group(['middleware' => ['auth:sanctum']], function () {
    ##
    Route::post('/register/add', 'Api\AirFlight\AirFlightController@add');

    ## Añade entradas por lotes json
    Route::post('/register/add-json', 'Api\AirFlight\AirFlightController@addJson');
});
