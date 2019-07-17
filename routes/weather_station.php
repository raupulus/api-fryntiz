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
Route::get('/humidity/find', 'HumidityController@find');

## Obtengo todos los datos de presión.
Route::get('/pressure/all', 'PressureController@all');

## Obtengo todos los datos de temperatura.
Route::get('/temperature/all', 'TemperatureController@all');

######################################################
##                    Privada
######################################################
Route::post('/humidity/add', 'HumidityController@add');
