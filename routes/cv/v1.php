<?php
/*
 * Archivo de rutas para la api de curriculum y sus condiciones con él
 * sufijo /api/cv/v1/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################


######################################################
##                    Privada
######################################################
Route::group(['middleware' => ['auth:sanctum']], function () {

});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
