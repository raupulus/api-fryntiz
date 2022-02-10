<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /api/weatherstation/v1/*
 */

use App\Http\Controllers\Api\Hardware\V1\SolarChargeController;
use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################

## Grupo de rutas para búsquedas de resultados en tablas.
Route::group(['prefix' => '/'], function () {
    /*
    Route::get('/solarcharge/test', function () {
        return response()->json(['test' => 'Valor']);
    });
    */
});

######################################################
##                    Privada
######################################################
Route::group(['middleware' => ['auth:sanctum']], function () {

    ## Añadir Datos Solares
    Route::post('/solarcharge/store', [SolarChargeController::class, 'store']);

    ## Añadir Datos de solo consumo
    //todo

});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
