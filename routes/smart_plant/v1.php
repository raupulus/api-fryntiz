<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /api/smartplant/v1/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
##
Route::group(['middleware' => ['auth:sanctum']], function () {
    ##
    Route::post('/register/add', 'App\Http\Controllers\Api\SmartPlant\SmartPlantController@add');

    ##
    Route::post('/register/add-json', 'App\Http\Controllers\Api\SmartPlant\SmartPlantController@addJson');
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
