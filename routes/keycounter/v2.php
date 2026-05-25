<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KeyCounter\V2\KeyboardController;
use App\Http\Controllers\Api\KeyCounter\V2\MouseController;

/*
|--------------------------------------------------------------------------
| API V2 Routes — KeyCounter
|--------------------------------------------------------------------------
*/

Route::prefix('keycounter')->middleware(['auth:sanctum', 'throttle:api-store'])->group(function () {
    Route::post('/keyboard', [KeyboardController::class, 'store'])->name('api.v2.keycounter.keyboard.store');
    Route::post('/mouse', [MouseController::class, 'store'])->name('api.v2.keycounter.mouse.store');
});
