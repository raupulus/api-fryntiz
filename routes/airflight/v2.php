<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AirFlight\V2\AirFlightController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — AirFlight
|--------------------------------------------------------------------------
|
| `GET /airflight/receiver` se conserva tal cual: hay **un** receptor ADS-B, y
| un recurso único sin colección es REST correcto.
|
| El alta por lotes también se conserva: el receptor manda hasta 500 aeronaves
| por barrido y partirlo en 500 peticiones no tiene sentido.
|
| Se han retirado dos endpoints:
|
|  - `GET /airflight/db/{bkey}` devolvía siempre 404: el dataset del registro
|    OACI no se mantiene. Un endpoint que sólo sabe decir «no encontrado» es
|    peor que no tenerlo, porque parece que existe.
|  - `GET /airflight/history` era la misma colección de aviones sin la ventana
|    de actividad reciente: ahora es `?from=&to=`.
|
*/

Route::prefix('airflight')->group(function () {
    // # Lecturas: exigen `airflight:read`.
    //
    // Estaban abiertas a cualquiera porque el mapa de `/airflight` las llamaba
    // desde el navegador. Eso dejaba la ability sin nada que proteger: una
    // casilla en el panel que no cambiaba nada. El mapa se sirve ahora desde
    // el bloque web (`routes/airflight/web.php`), ya cacheado, y aquí queda lo
    // que es de verdad una integración: historial por fechas, filtros, orden y
    // paginación.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::AIRFLIGHT_READ, 'throttle:api'])->group(function () {
        Route::get('/aircrafts', [AirFlightController::class, 'aircrafts'])->name('api.v2.airflight.aircrafts.index');
        Route::get('/receiver', [AirFlightController::class, 'receiver'])->name('api.v2.airflight.receiver.show');
    });

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::AIRFLIGHT_WRITE])->group(function () {
        Route::post('/aircrafts', [AirFlightController::class, 'store'])
            ->middleware('throttle:api-store')
            ->name('api.v2.airflight.aircrafts.store');

        Route::post('/aircrafts/batch', [AirFlightController::class, 'storeBatch'])
            ->middleware('throttle:api-store-batch')
            ->name('api.v2.airflight.aircrafts.store_batch');
    });
});
