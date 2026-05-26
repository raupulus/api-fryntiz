<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Generales
|--------------------------------------------------------------------------
*/

// Auth
Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Auth\V2\LoginController::class, 'login'])->name('api.v2.auth.login');
    Route::post('/signup', [\App\Http\Controllers\Api\Auth\V2\RegisterController::class, 'create'])->name('api.v2.auth.signup');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\Auth\V2\LoginController::class, 'logout'])->name('api.v2.auth.logout');
        Route::post('/delete-account', [\App\Http\Controllers\Api\Auth\V2\RegisterController::class, 'destroy'])->name('api.v2.auth.delete_account');
    });
});

// Contact
Route::prefix('contact')->middleware(['throttle:contact'])->group(function () {
    Route::post('/send', [\App\Http\Controllers\Api\Contact\V2\ContactController::class, 'send'])->name('api.v2.contact.send');
});

// Newsletter
Route::prefix('newsletter')->middleware('throttle:api-auth')->group(function () {
    Route::post('/subscribe', [\App\Http\Controllers\Api\Newsletter\V2\NewsletterController::class, 'subscribe'])->name('api.v2.newsletter.subscribe');
    Route::get('/verify/{token}', [\App\Http\Controllers\Api\Newsletter\V2\NewsletterController::class, 'verify'])->name('api.v2.newsletter.verify');
    Route::get('/unsubscribe/{token}', [\App\Http\Controllers\Api\Newsletter\V2\NewsletterController::class, 'unsubscribe'])->name('api.v2.newsletter.unsubscribe');
});

// Users
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\User\V2\UserController::class, 'index'])->name('api.v2.user.index');
    Route::get('/{user}', [\App\Http\Controllers\Api\User\V2\UserController::class, 'show'])->name('api.v2.user.show');
    Route::put('/{user}', [\App\Http\Controllers\Api\User\V2\UserController::class, 'update'])->name('api.v2.user.update');
    Route::delete('/{user}', [\App\Http\Controllers\Api\User\V2\UserController::class, 'destroy'])->name('api.v2.user.destroy');
});

// Content
Route::prefix('content')->group(function () {
    Route::get('/{content:slug}/pages', [\App\Http\Controllers\Api\Content\V2\ContentController::class, 'pages'])->name('api.v2.content.pages');
    Route::get('/{content:slug}/related', [\App\Http\Controllers\Api\Content\V2\ContentController::class, 'related'])->name('api.v2.content.related');
    Route::get('/{platform:slug}/{content:slug}', [\App\Http\Controllers\Api\Content\V2\ContentController::class, 'show'])->name('api.v2.content.show');
});

// Platforms
Route::prefix('platform')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Platform\V2\PlatformController::class, 'index'])->name('api.v2.platform.index');
    Route::get('/{platform:slug}', [\App\Http\Controllers\Api\Platform\V2\PlatformController::class, 'show'])->name('api.v2.platform.show');
    Route::get('/{platform:slug}/featured', [\App\Http\Controllers\Api\Platform\V2\PlatformController::class, 'featured'])->name('api.v2.platform.featured');
});

/*
|--------------------------------------------------------------------------
| Rutas V2 por módulo (archivos individuales)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../airflight/v2.php';
require __DIR__ . '/../hardware/v2.php';
require __DIR__ . '/../keycounter/v2.php';
require __DIR__ . '/../smart_plant/v2.php';
require __DIR__ . '/../weather_station/v2.php';
require __DIR__ . '/../cv/v2.php';

// Fallback
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API V2 - Endpoint no encontrado',
    ], 404);
});
