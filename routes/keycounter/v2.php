<?php

declare(strict_types=1);

use App\Http\Controllers\Api\KeyCounter\V2\KeyboardController;
use App\Http\Controllers\Api\KeyCounter\V2\MouseController;
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

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::KEYCOUNTER_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/keyboard-sessions', [KeyboardController::class, 'store'])->name('api.v2.keycounter.keyboard_sessions.store');
        Route::post('/mouse-sessions', [MouseController::class, 'store'])->name('api.v2.keycounter.mouse_sessions.store');
    });
});
