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
Route::get('/aircrafts', [AirFlightController::class, 'aircrafts'])->name('airflight.aircrafts');
Route::get('/receiver', [AirFlightController::class, 'receiver'])->name('airflight.receiver');
