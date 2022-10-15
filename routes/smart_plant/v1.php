<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /api/smartplant/v1/*
 */

use App\Http\Controllers\Api\SmartPlant\V1\SmartPlantController;
use App\Http\Controllers\Api\SmartPlant\V1\SmartPlantRegisterController;
use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
##
Route::group(['middleware' => ['auth:sanctum']], function () {
    ## Guarda un registro de planta
    Route::post('/register/store', [SmartPlantRegisterController::class, 'store'])
        ->name('api.smartplant.v1.register.store');
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function() {
    return response()->json(['message' => 'Page Not Found'], 404);
});
