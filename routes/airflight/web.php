<?php

declare(strict_types=1);

/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

use App\Http\Controllers\AirFlight\AirFlightController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AirFlightController::class, 'index'])->name('airflight.index');

// # Datos del mapa de esta misma web.
//
// No es API: sin token, cacheado y con lo justo que pinta el mapa. Antes el
// mapa llamaba a `GET /api/v2/airflight/*`, que por eso tenía que estar
// abierta y dejaba la ability `airflight:read` sin nada que proteger.
//
// Sin token no hay nada que compruebe quién llama, así que las tres llevan
// `same-origin` (sólo desde la propia página, ver
// `EnsureRequestIsSameOrigin`) y `throttle:public-widget` (freno al scraping
// sostenido, no a la navegación normal ni al sondeo de `detected`).
Route::middleware(['same-origin', 'throttle:public-widget'])->group(function () {
    Route::get('/aircrafts', [AirFlightController::class, 'aircrafts'])->name('airflight.aircrafts');
    Route::get('/receiver', [AirFlightController::class, 'receiver'])->name('airflight.receiver');

    // Aviones detectados en la última hora, para refrescar la tabla de esta
    // misma vista por sondeo (cada minuto) sin recargar la página.
    Route::get('/detected', [AirFlightController::class, 'detected'])->name('airflight.detected');
});
