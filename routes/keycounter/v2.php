<?php

declare(strict_types=1);

use App\Http\Controllers\Api\KeyCounter\V2\KeyboardController;
use App\Http\Controllers\Api\KeyCounter\V2\MouseController;
use App\Http\Controllers\Api\KeyCounter\V2\SummaryController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — KeyCounter
|--------------------------------------------------------------------------
|
| El recurso es la **sesión de trabajo** (`start_at`/`end_at`), no el teclado:
| `POST /keycounter/keyboard` sonaba a que se creaba un teclado.
|
| Se añade la lectura, que no existía: los datos sólo se veían por Blade.
|
*/

Route::prefix('keycounter')->group(function () {
    // Leer exige `keycounter:read`; escribir, `keycounter:write` (AR-S02).
    // Antes las dos cosas iban con `:write`, así que el token de un teclado
    // —que sólo tiene que subir pulsaciones— podía listar todas las sesiones de
    // su dueño.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::KEYCOUNTER_READ, 'throttle:api'])->group(function () {
        Route::get('/keyboard-sessions', [KeyboardController::class, 'index'])->name('api.v2.keycounter.keyboard_sessions.index');
        Route::get('/mouse-sessions', [MouseController::class, 'index'])->name('api.v2.keycounter.mouse_sessions.index');
    });

    // # Resumen acumulado de un periodo.
    //
    // Lo pide un contador **al arrancar**: apagarse o reiniciarse le borra lo
    // que llevaba del día, y sin esto empieza de cero y enseña un total falso
    // hasta medianoche.
    //
    // Límite propio (20/min) y no el general de lectura: es una consulta
    // agregada sobre una tabla de millones de filas y se pide una vez por
    // arranque, no en bucle.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::KEYCOUNTER_READ, 'throttle:keycounter-summary'])
        ->get('/summary', [SummaryController::class, 'show'])
        ->name('api.v2.keycounter.summary');

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::KEYCOUNTER_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/keyboard-sessions', [KeyboardController::class, 'store'])->name('api.v2.keycounter.keyboard_sessions.store');
        Route::post('/mouse-sessions', [MouseController::class, 'store'])->name('api.v2.keycounter.mouse_sessions.store');
    });
});
