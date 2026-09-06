<?php

declare(strict_types=1);

use App\Http\Controllers\Api\WeatherStation\V2\SensorReadingController;
use App\Http\Controllers\Api\WeatherStation\V2\WeatherStationController;
use App\Support\Auth\TokenAbilities;
use App\Support\WeatherStation\SensorCatalog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Estación meteorológica
|--------------------------------------------------------------------------
|
| Las lecturas cuelgan de su estación. Antes la URL no decía de qué estación
| eran:
|
|   ANTES:  GET /weatherstation/temperature
|           → todas las temperaturas de todas las estaciones, mezcladas, sin
|             paginar y sin poder filtrar
|
|   AHORA:  GET /weather-stations/3/temperatures
|           → las de LA estación 3, paginadas
|
| Con una estación daba igual; con varias, la URL de antes no dejaba pedir la
| que quieres. Y sobre una tabla de serie temporal de millones de filas, un
| `->get()` sin acotar no es una respuesta lenta: es el servidor caído.
|
| Se mantiene un endpoint por sensor (D33): así se puede emitir un token que
| sólo suba luz y radiación y no el resto.
|
*/

$sensors = SensorCatalog::routePattern();

Route::prefix('weather-stations')->group(function () use ($sensors) {
    // # Lecturas públicas
    Route::get('/', [WeatherStationController::class, 'index'])->name('api.v2.weather_stations.index');
    Route::get('/{station}', [WeatherStationController::class, 'show'])
        ->whereNumber('station')
        ->name('api.v2.weather_stations.show');

    // Lectura por zona: el dato más reciente de cada sensor entre todas las
    // estaciones de esa zona. Va antes que `/{station}/{sensor}` para que
    // «zone» no se coma como si fuera un id — de ahí el `whereNumber` de la
    // ruta de arriba, que ya lo impedía, y este orden que lo deja explícito.
    Route::get('/zone/{zone}/{locationType?}', [WeatherStationController::class, 'zone'])
        ->where('locationType', 'indoor|outdoor')
        ->name('api.v2.weather_stations.zone');

    Route::get('/{station}/{sensor}', [SensorReadingController::class, 'index'])
        ->whereNumber('station')
        ->where('sensor', $sensors)
        ->name('api.v2.weather_stations.readings.index');

    // # Escrituras IoT
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::WEATHERSTATION_WRITE, 'throttle:api-store'])
        ->group(function () use ($sensors) {
            // Lote multi-sensor. Excepción consciente al REST: en REST puro
            // serían once peticiones, y para un microcontrolador con batería
            // once peticiones son once veces el coste de radio.
            Route::post('/{station}/readings', [SensorReadingController::class, 'storeReadings'])
                ->whereNumber('station')
                ->middleware('throttle:api-store-batch')
                ->name('api.v2.weather_stations.readings.store_batch');

            // Una lectura, o un lote de ese sensor con {"readings": [...]} (C2).
            Route::post('/{station}/{sensor}', [SensorReadingController::class, 'store'])
                ->whereNumber('station')
                ->where('sensor', $sensors)
                ->name('api.v2.weather_stations.readings.store');
        });
});
