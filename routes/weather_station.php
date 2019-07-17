<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /ws/*
 */


use Illuminate\Http\Request;

######################################################
##                    Pública
######################################################
Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

## Obtengo todos los datos de humedad.
Route::get('/humidity', 'HumidityController@all');

## Obtengo todos los datos de presión.
Route::get('/pressure', 'PressureController@all');

## Obtengo todos los datos de temperatura.
Route::get('/temperature', 'TemperatureController@all');

######################################################
##                    Privada
######################################################
