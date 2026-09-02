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
    // Leer exige `smartplant:read`; escribir, `smartplant:write` (AR-S02).
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::SMARTPLANT_READ, 'throttle:api'])->group(function () {
        Route::get('/plants', [SmartPlantRegisterController::class, 'plants'])->name('api.v2.smartplant.plants.index');
        Route::get('/plants/{plant}/readings', [SmartPlantRegisterController::class, 'index'])
            ->whereNumber('plant')
            ->name('api.v2.smartplant.plants.readings.index');
    });

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::SMARTPLANT_WRITE, 'throttle:api-store'])->group(function () {
        Route::post('/plants/{plant}/readings', [SmartPlantRegisterController::class, 'store'])
            ->whereNumber('plant')
            ->name('api.v2.smartplant.plants.readings.store');
    });
});
