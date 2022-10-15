<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /airflight/*
 */

use App\Http\Controllers\AirFlight\AirFlightController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AirFlightController::class, 'index'])->name('airflight.index');
