<?php
/*
 * Archivo de rutas para la api para la pulsación de teclas y ratón con él
 * sufijo /api/keycounter/v1/*
 */

use App\Http\Controllers\Api\KeyCounter\V1\KeyboardController;
use App\Http\Controllers\Api\KeyCounter\V1\MouseController;
use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
Route::group(['middleware' => ['auth:sanctum']], function () {
    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/keyboard/store', [KeyboardController::class, 'store'] );

    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/mouse/store', [MouseController::class, 'store'] );
});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
