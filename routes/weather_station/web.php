<?php
/*
 * Archivo de rutas para la web de estación meteorológica accesible desde el
 * sufijo /weatherstation/*
 */

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/'], function () {
    ## Muestra la vista de resumen para depurar los datos
    Route::get('/', 'App\Http\Controllers\WeatherStation\WeatherStationController@index')
        ->name('weather_station.index');
});
