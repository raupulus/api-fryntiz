<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SmartPlant\V2\SmartPlantRegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Smart Plant
|--------------------------------------------------------------------------
*/

Route::prefix('smartplant')->middleware(['auth:sanctum', 'ability:smartplant:write', 'throttle:api-store'])->group(function () {
    Route::post('/register', [SmartPlantRegisterController::class, 'store'])->name('api.v2.smartplant.register.store');
});
