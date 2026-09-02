<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SmartPlant\V2\SmartPlantRegisterController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Smart Plant
|--------------------------------------------------------------------------
|
| La lectura cuelga de su planta. No es cosmético: `smartplant_registers` no
| tiene columna `user_id` (N288), así que la planta es el único sitio donde
| consta de quién es una lectura. Con `plant_id` suelto en el cuerpo y validado
| sólo con `exists`, cualquiera con la ability podía escribir en la planta de
| otro (H5).
|
*/

Route::prefix('smartplant')->group(function () {
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::SMARTPLANT_WRITE])->group(function () {
        // AR-A01: las lecturas iban sin throttle, y `plants/{plant}/readings`
        // pagina una tabla de serie temporal.
        Route::middleware('throttle:api')->group(function () {
            Route::get('/plants', [SmartPlantRegisterController::class, 'plants'])->name('api.v2.smartplant.plants.index');
            Route::get('/plants/{plant}/readings', [SmartPlantRegisterController::class, 'index'])
                ->whereNumber('plant')
                ->name('api.v2.smartplant.plants.readings.index');
        });

        Route::post('/plants/{plant}/readings', [SmartPlantRegisterController::class, 'store'])
            ->whereNumber('plant')
            ->middleware('throttle:api-store')
            ->name('api.v2.smartplant.plants.readings.store');
    });
});
