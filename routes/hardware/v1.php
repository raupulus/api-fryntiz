<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /api/hardware/v1/*
 */

use App\Http\Controllers\Api\Hardware\V1\EnergyMonitorController;
use App\Http\Controllers\Api\Hardware\V1\SolarChargeController;
use \App\Http\Controllers\Api\Hardware\V1\HardwareDeviceController;
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

    ## Obtener información de un dispositivo concreto
    Route::get('/get/device/{id}/info', [HardwareDeviceController::class, 'getDeviceInfo']);

    ## Obtener listado de equipos/PCs
    Route::get('/get/computers/list', [HardwareDeviceController::class, 'getComputersList']);

    ## Añadir Datos Energía
    Route::post('/energy-monitor/store', [EnergyMonitorController::class, 'store']);

    ## Añadir Datos Solares
    Route::post('/solarcharge/store', [SolarChargeController::class, 'store']);
});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
