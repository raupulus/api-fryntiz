<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /ws/*
 */

######################################################
##                    Pública
######################################################

## Obtengo un resumen con todos los datos principales.
Route::get('/resume', '\App\Api\WeatherStation\Controllers\GeneralController@resume');

## Obtengo todos los datos de humedad.
//Route::get('/humidity/all', 'HumidityController@all');

## Busco un dato concreto para humedad.
//Route::get('/humidity/find', 'HumidityController@find');

## Obtengo todos los datos de presión.
//Route::get('/pressure/all', 'PressureController@all');

## Busco un dato concreto para presión.
//Route::get('/pressure/find', 'PressureController@find');

## Obtengo todos los datos de temperatura.
//Route::get('/temperature/all', 'TemperatureController@all');

## Busco un dato concreto para temperatura.
//Route::get('/temperature/find', 'TemperatureController@find');

## Obtengo todos los datos de Luz.
//Route::get('/light/all', 'LightController@all');

## Busco un dato concreto para Luz.
//Route::get('/light/find', 'LightController@find');

## Obtengo todos los datos de Uv.
//Route::get('/uv/all', 'UvController@all');

## Busco un dato concreto para Uv.
//Route::get('/uv/find', 'UvController@find');

## Obtengo todos los datos de Viento.
//Route::get('/winter/all', 'WinterController@all');

## Busco un dato concreto para Viento.
//Route::get('/winter/find', 'WinterController@find');

## Obtengo todos los datos de Eco2.
//Route::get('/eco2/all', 'Eco2Controller@all');

## Busco un dato concreto para Eco2.
//Route::get('/eco2/find', 'Eco2Controller@find');

## Obtengo todos los datos de TVOC.
//Route::get('/tvoc/all', 'TvocController@all');

## Busco un dato concreto para TVOC.
//Route::get('/tvoc/find', 'TvocController@find');

## Obtengo todos los datos de Índice UV.
//Route::get('/uv_index/all', 'UvIndexController@all');

## Busco un dato concreto para Índice UV.
//Route::get('/uv_index/find', 'UvIndexController@find');

## Obtengo todos los datos de Rayos UVA.
//Route::get('/uva/all', 'UvaController@all');

## Busco un dato concreto para Rayos UVA.
//Route::get('/uva/find', 'UvaController@find');

## Obtengo todos los datos de Rayos UVB.
//Route::get('/uvb/all', 'UvbController@all');

## Busco un dato concreto para Rayos UVB.
//Route::get('/uvb/find', 'UvbController@find');

## Obtengo todos los datos de Air Quality.
//Route::get('/air_quality/all', 'AirQualityController@all');

## Busco un dato concreto para Air Quality.
//Route::get('/air_quality/find', 'AirQualityController@find');

## Obtengo todos los datos de Rayos.
//Route::get('/lightning/all', 'LightningController@all');

## Busco un dato concreto para Rayos.
//Route::get('/lightning/find', 'LightningController@find');


######################################################
##                    Privada
######################################################
Route::group([
        'prefix' => '/',
        'middleware' => ['auth:api']
    ], function () {
        ## Añadir nuevo registro de humedad.
        Route::post('/humidity/add', 'HumidityController@add');

        ## Añadir nuevos registros de humedad por lotes JSON
        Route::post('/humidity/add-json', 'HumidityController@addJson');

        ## Añadir nuevo registro de presión.
        Route::post('/pressure/add', 'PressureController@add');

        ## Añadir nuevos registros de presión por lotes JSON
        Route::post('/pressure/add-json', 'PressureController@addJson');

        ## Añadir nuevo registro de temperatura.
        Route::post('/temperature/add', 'TemperatureController@add');

        ## Añadir nuevos registros de temperatura por lotes JSON
        Route::post('/temperature/add-json', 'TemperatureController@addJson');

        ## Añadir nuevo registro de luz.
        Route::post('/light/add', 'LightController@add');

        ## Añadir nuevos registros de Luz por lotes JSON
        Route::post('/light/add-json', 'LightController@addJson');

        ## Añadir nuevo registro de rayos uv.
        Route::post('/uv/add', 'UvController@add');

        ## Añadir nuevos registros de rayos uv por lotes JSON
        Route::post('/uv/add-json', 'UvController@addJson');

        ## Añadir nuevo registro de viento.
        Route::post('/winter/add', 'WinterController@add');

        ## Añadir nuevos registros de viento por lotes JSON
        Route::post('/winter/add-json', 'WinterController@addJson');

        ## Añadir nuevo registro de CO2.
        Route::post('/eco2/add', 'Eco2Controller@add');

        ## Añadir nuevos registros de CO2 por lotes JSON
        Route::post('/eco2/add-json', 'Eco2Controller@addJson');

        ## Añadir nuevo registro de TVOC.
        Route::post('/tvoc/add', 'TvocController@add');

        ## Añadir nuevos registros de TVOC por lotes JSON
        Route::post('/tvoc/add-json', 'TvocController@addJson');

        ## Añadir nuevo registro de Índice UV.
        Route::post('/uv_index/add', 'UvIndexController@add');

        ## Añadir nuevos registros de Índice UV por lotes JSON
        Route::post('/uv_index/add-json', 'UvIndexController@addJson');

        ## Añadir nuevo registro de UVA.
        Route::post('/uva/add', 'UvaController@add');

        ## Añadir nuevos registros de UVA por lotes JSON
        Route::post('/uva/add-json', 'UvaController@addJson');

        ## Añadir nuevo registro de UVB.
        Route::post('/uvb/add', 'UvbController@add');

        ## Añadir nuevos registros de UVB por lotes JSON
        Route::post('/uvb/add-json', 'UvbController@addJson');

        ## Añadir nuevo registro de Air Quality.
        Route::post('/air_quality/add', 'AirQualityController@add');

        ## Añadir nuevos registros de Air Quality por lotes JSON
        Route::post('/air_quality/add-json', 'AirQualityController@addJson');

        ## Añadir nuevo registro de Rayos.
        Route::post('/lightning/add', 'LightningController@add');

        ## Añadir nuevos registros de Rayos por lotes JSON
        Route::post('/lightning/add-json', 'LightningController@addJson');
    }
);

