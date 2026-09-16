<?php

declare(strict_types=1);

/*
 * Archivo de rutas para la web de estación meteorológica accesible desde el
 * sufijo /weatherstation/*
 */

use App\Http\Controllers\Gdacs\GdacsController;
use App\Http\Controllers\WeatherStation\WeatherStationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/'], function () {
    // # Muestra la vista de resumen para depurar los datos
    Route::get('/', 'App\Http\Controllers\WeatherStation\WeatherStationController@index')
        ->name('weather_station.index');

    // # Muestra los datos de un sensor individual
    Route::get('/sensor/{type}', 'App\Http\Controllers\WeatherStation\WeatherStationController@sensor')
        ->name('weather_station.sensor');

    // # Listado paginado de alertas GDACS cerca de Chipiona
    Route::get('/gdacs', [GdacsController::class, 'index'])
        ->name('weather_station.gdacs.index');

    // # Datos del widget del clima de esta misma web.
    //
    // Es lo que consume el componente Vue de la portada y de esta página. No
    // es API: no lleva token, va cacheado y devuelve exactamente la lectura
    // que se pinta. Antes llamaba a `GET /api/v2/weather-stations`, y por eso
    // esa ruta de API tenía que estar abierta a cualquiera.
    //
    // Sin token no hay nada que compruebe quién llama, así que las tres
    // llevan `same-origin` (sólo desde la propia página, ver
    // `EnsureRequestIsSameOrigin`) y `throttle:public-widget` (freno al
    // scraping sostenido, no a la navegación normal).
    Route::middleware(['same-origin', 'throttle:public-widget'])->group(function () {
        Route::get('/widget', [WeatherStationController::class, 'widget'])
            ->name('weather_station.widget');

        Route::get('/widget/zone/{zone}/{locationType?}', [WeatherStationController::class, 'widget'])
            ->name('weather_station.widget.zone');

        // El id va en el mismo sitio que la zona; `whereNumber` los separa.
        Route::get('/widget/{station}', [WeatherStationController::class, 'widgetStation'])
            ->whereNumber('station')
            ->name('weather_station.widget.station');
    });
});
