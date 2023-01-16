<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /api/weatherstation/v1/*
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WeatherStation;

######################################################
##                    Pública
######################################################

## Obtengo un resumen con todos los datos principales.
Route::match(['get', 'options'], '/resume', 'App\Http\Controllers\Api\WeatherStation\GeneralController@resume')
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/temperature',
    [WeatherStation\TemperatureController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/humidity',
    [WeatherStation\HumidityController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/pressure',
    [WeatherStation\PressureController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/wind',
    [WeatherStation\WinterController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/light',
    [WeatherStation\LightController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/air-quality',
    [WeatherStation\AirQualityController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/rain',
    [WeatherStation\RainController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');

Route::match(['get', 'options'], '/lightning',
    [WeatherStation\LightningController::class, 'getPrepareData'])
    ->middleware('cors.allow.all');


## Grupo de rutas para búsquedas de resultados en tablas.
Route::group(['prefix' => 'table'], function () {

    ## Obtengo todos los datos de temperatura.
    Route::match(['post', 'options'],'/temperature', [WeatherStation\TemperatureController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.temperature');

    ## Obtengo todos los datos de humedad.
    Route::match(['post', 'options'],'/humidity', [WeatherStation\HumidityController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.humidity');

    ## Obtengo todos los datos de presión atmosférica.
    Route::match(['post', 'options'],'/pressure', [WeatherStation\PressureController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.pressure');

    ## Obtengo todos los datos de Luz.
    Route::match(['post', 'options'],'/light', [WeatherStation\LightController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.light');

    ## Obtengo todos los datos de Viento.
    Route::match(['post', 'options'],'/winter', [WeatherStation\WinterController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.winter');

    Route::match(['post', 'options'],'/wind-direction',
        [WeatherStation\WindDirectionController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.wind_direction');

    Route::match(['post', 'options'],'/rain',
        [WeatherStation\RainController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.rain');

    ## Obtengo todos los datos de CO2.
    Route::match(['post', 'options'],'/eco2', [WeatherStation\Eco2Controller::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.eco2');

    ## Obtengo todos los datos de TVOC.
    Route::match(['post', 'options'],'/tvoc', [WeatherStation\TvocController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.tvoc');

    ## Obtengo todos los datos de Calidad de aire.
    Route::match(['post', 'options'],'/air_quality', [WeatherStation\AirQualityController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.air_quality');

    ## Obtengo todos los datos de Rayos.
    Route::match(['post', 'options'],'/lightning', [WeatherStation\LightningController::class, 'getTableDataSearchJson'])
        ->name('api.wheaterstation.v1.table.lightning');
});

######################################################
##                    Privada
######################################################
Route::group(['middleware' => ['auth:sanctum']], function () {

    ## Subida de datos por estación meteorológica.
    Route::post('/generic/add/json', [WeatherStation\GenericWSController::class,
        'add'])
        ->middleware('cors.allow.all')
        ->name('api.wheaterstation.v1.generic.add.json');



    ## Añadir nuevo registro de humedad.
    Route::post('/humidity/add', 'App\Http\Controllers\Api\WeatherStation\HumidityController@add');

    ## Añadir nuevos registros de humedad por lotes JSON
    Route::post('/humidity/add-json', 'App\Http\Controllers\Api\WeatherStation\HumidityController@addJson');

    ## Añadir nuevo registro de presión.
    Route::post('/pressure/add', 'App\Http\Controllers\Api\WeatherStation\PressureController@add');

    ## Añadir nuevos registros de presión por lotes JSON
    Route::post('/pressure/add-json', 'App\Http\Controllers\Api\WeatherStation\PressureController@addJson');

    ## Añadir nuevo registro de temperatura.
    Route::post('/temperature/add', 'App\Http\Controllers\Api\WeatherStation\TemperatureController@add');

    ## Añadir nuevos registros de temperatura por lotes JSON
    Route::post('/temperature/add-json', 'App\Http\Controllers\Api\WeatherStation\TemperatureController@addJson');

    ## Añadir nuevo registro de luz.
    Route::post('/light/add', 'App\Http\Controllers\Api\WeatherStation\LightController@add');

    ## Añadir nuevos registros de Luz por lotes JSON
    Route::post('/light/add-json', 'App\Http\Controllers\Api\WeatherStation\LightController@addJson');

    ## Añadir nuevo registro de viento.
    Route::post('/winter/add', 'App\Http\Controllers\Api\WeatherStation\WinterController@add');

    ## Añadir nuevos registros de viento por lotes JSON
    Route::post('/winter/add-json', 'App\Http\Controllers\Api\WeatherStation\WinterController@addJson');

    ## Añadir nuevo registro de CO2.
    Route::post('/eco2/add', 'App\Http\Controllers\Api\WeatherStation\Eco2Controller@add');

    ## Añadir nuevos registros de CO2 por lotes JSON
    Route::post('/eco2/add-json', 'App\Http\Controllers\Api\WeatherStation\Eco2Controller@addJson');

    ## Añadir nuevo registro de TVOC.
    Route::post('/tvoc/add', 'App\Http\Controllers\Api\WeatherStation\TvocController@add');

    ## Añadir nuevos registros de TVOC por lotes JSON
    Route::post('/tvoc/add-json', 'App\Http\Controllers\Api\WeatherStation\TvocController@addJson');

    ## Añadir nuevo registro de Air Quality.
    Route::post('/air_quality/add', 'App\Http\Controllers\Api\WeatherStation\AirQualityController@add');

    ## Añadir nuevos registros de Air Quality por lotes JSON
    Route::post('/air_quality/add-json', 'App\Http\Controllers\Api\WeatherStation\AirQualityController@addJson');

    ## Añadir nuevo registro de Rayos.
    Route::post('/lightning/add', 'App\Http\Controllers\Api\WeatherStation\LightningController@add');

    ## Añadir nuevos registros de Rayos por lotes JSON
    Route::post('/lightning/add-json', 'App\Http\Controllers\Api\WeatherStation\LightningController@addJson');
});





/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
