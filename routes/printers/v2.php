<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Printers\V2\PrinterController;
use App\Http\Controllers\Api\Printers\V2\PrintJobController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Impresoras y Cola de Impresión
|--------------------------------------------------------------------------
|
| Endpoints para la gestión de periféricos de impresión física y el consumo
| atómico de trabajos por parte de microcontroladores (ESP32, Pico W, etc.).
|
*/

Route::prefix('printers')->group(function () {
    // ── Lectura de impresoras y colas (Dashboard / Clientes / Sesión) ────────
    Route::middleware([
        'auth:sanctum',
        'ability:'.TokenAbilities::PRINTERS_READ.','.TokenAbilities::SESSION.','.TokenAbilities::PRINTERS_WRITE,
        'throttle:api',
    ])->group(function () {
        Route::get('/', [PrinterController::class, 'index'])->name('api.v2.printers.index');
        Route::get('/{printer}', [PrinterController::class, 'show'])
            ->whereNumber('printer')
            ->name('api.v2.printers.show');

        Route::get('/{printer}/jobs', [PrintJobController::class, 'index'])
            ->whereNumber('printer')
            ->name('api.v2.printers.jobs.index');

        Route::get('/{printer}/favorites', [PrintJobController::class, 'favorites'])
            ->whereNumber('printer')
            ->name('api.v2.printers.favorites.index');

        Route::get('/jobs/{job}', [PrintJobController::class, 'show'])
            ->whereNumber('job')
            ->name('api.v2.printers.jobs.show');
    });

    // ── Encolado y gestión de trabajos (Emisores / Sesión) ───────────────────
    Route::middleware([
        'auth:sanctum',
        'ability:'.TokenAbilities::PRINTERS_WRITE.','.TokenAbilities::SESSION,
        'throttle:api-store',
    ])->group(function () {
        Route::post('/{printer}/jobs', [PrintJobController::class, 'store'])
            ->whereNumber('printer')
            ->name('api.v2.printers.jobs.store');

        Route::post('/jobs/{job}/reprint', [PrintJobController::class, 'reprint'])
            ->whereNumber('job')
            ->name('api.v2.printers.jobs.reprint');
    });

    // ── Modificación de favoritos y cancelación de trabajos ─────────────────
    Route::middleware([
        'auth:sanctum',
        'ability:'.TokenAbilities::PRINTERS_WRITE.','.TokenAbilities::SESSION,
        'throttle:api',
    ])->group(function () {
        Route::patch('/jobs/{job}/favorite', [PrintJobController::class, 'toggleFavorite'])
            ->whereNumber('job')
            ->name('api.v2.printers.jobs.favorite');

        Route::delete('/jobs/{job}', [PrintJobController::class, 'destroy'])
            ->whereNumber('job')
            ->name('api.v2.printers.jobs.destroy');
    });

    // ── Operaciones IoT exclusivas del Microcontrolador / Agente ─────────────
    Route::middleware([
        'auth:sanctum',
        'ability:'.TokenAbilities::PRINTERS_WRITE,
        'throttle:api-store',
    ])->group(function () {
        Route::post('/{printer}/jobs/next', [PrintJobController::class, 'claimNext'])
            ->whereNumber('printer')
            ->name('api.v2.printers.jobs.next');

        Route::patch('/jobs/{job}/status', [PrintJobController::class, 'updateStatus'])
            ->whereNumber('job')
            ->name('api.v2.printers.jobs.status');

        Route::post('/{printer}/heartbeat', [PrinterController::class, 'heartbeat'])
            ->whereNumber('printer')
            ->name('api.v2.printers.heartbeat');
    });
});
