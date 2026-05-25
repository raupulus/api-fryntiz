<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SmartPlant\V2\SmartPlantRegisterController;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Smart Plant
|--------------------------------------------------------------------------
*/

Route::prefix('smartplant')->middleware(['auth:sanctum', 'throttle:api-store'])->group(function () {
    Route::post('/register', [SmartPlantRegisterController::class, 'store'])->name('api.v2.smartplant.register.store');
});
