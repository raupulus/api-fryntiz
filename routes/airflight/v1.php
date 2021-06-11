<?php
/*
 * Archivo de rutas para la api de registros de aviones y rutas/vuelos
 *  con él sufijo /api/airflight/v1/*
 */

use App\Http\Controllers\Api\AirFlight\AirFlightController;
use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
Route::get('/get/aircrafts/json', [AirFlightController::class, 'getAircraftjson']);
Route::get('/get/receiver/json', [AirFlightController::class, 'getReceiverInformation']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    ##
    Route::post('/register/add', [AirFlightController::class, 'add']);

    ## Añade entradas por lotes json
    #Route::post('/register/add-json','Api\AirFlight\AirFlightController@addJson');
    Route::post('/register/add-json', [AirFlightController::class, 'addJson']);
});
