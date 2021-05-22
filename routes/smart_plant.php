<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /smartplant/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################
Route::get('/', 'App\Http\Controllers\SmartPlant\SmartPlantController@index')->name('smartplant.index');

Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:sanctum']
], function () {
    ##
    Route::post('/register/add', 'App\Http\Controllers\Api\SmartPlant\SmartPlantController@add');

    ##
    Route::post('/register/add-json', 'App\Http\Controllers\Api\SmartPlant\SmartPlantController@addJson');
});

