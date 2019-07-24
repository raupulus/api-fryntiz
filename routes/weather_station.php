<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /ws/*
 */

######################################################
##                    Pública
######################################################
Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

## Obtengo todos los datos de humedad.
Route::get('/humidity/all', 'HumidityController@all');

## Busco un dato concreto para humedad
Route::get('/humidity/find', 'HumidityController@find');

## Obtengo todos los datos de presión.
Route::get('/pressure/all', 'PressureController@all');

## Busco un dato concreto para presión
Route::get('/pressure/find', 'PressureController@find');

## Obtengo todos los datos de temperatura.
Route::get('/temperature/all', 'TemperatureController@all');

## Busco un dato concreto para temperatura
Route::get('/temperature/find', 'TemperatureController@find');

######################################################
##                    Privada
######################################################
Route::group([
        'prefix' => '/',
        'middleware' => ['auth:api']
    ], function () {
        ## Añadir nuevo registro de humedad.
        Route::post('/humidity/add', 'HumidityController@add');

        ## Añadir nuevos registros de humedad por lotes
        Route::post('/humidity/add-json', 'HumidityController@addJson');

        ## Añadir nuevo registro de Presión.
        Route::post('/pressure/add', 'PressureController@add');

        ## Añadir nuevos registros de humedad por lotes
        Route::post('/pressure/add-json', 'PressureController@addJson');

        ## Añadir nuevo registro de temperatura.
        Route::post('/temperature/add', 'TemperatureController@add');

        ## Añadir nuevos registros de humedad por lotes
        Route::post('/temperature/add-json', 'TemperatureController@addJson');
    }
);

