<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /smartplant/*
 */

######################################################
##                    Pública
######################################################
Route::get('/', 'SmartPlant\SmartPlantController@index')->name('smartplant.index');

Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:api']
], function () {
    ##
    Route::post('/add', 'SmartPlant\SmartPlantController@add');

    ##
    Route::post('/add-json', 'SmartPlant\SmartPlantController@addJson');
});

