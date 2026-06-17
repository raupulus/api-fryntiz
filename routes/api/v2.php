<?php

use App\Http\Controllers\Api\Auth\V2\LoginController;
use App\Http\Controllers\Api\Auth\V2\RegisterController;
use App\Http\Controllers\Api\Contact\V2\ContactController;
use App\Http\Controllers\Api\Content\V2\ContentController;
use App\Http\Controllers\Api\Newsletter\V2\NewsletterController;
use App\Http\Controllers\Api\Platform\V2\PlatformController;
use App\Http\Controllers\Api\User\V2\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — Generales
|--------------------------------------------------------------------------
*/

// Auth
Route::prefix('auth')->middleware('throttle:api-auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login'])->name('api.v2.auth.login');
    Route::post('/signup', [RegisterController::class, 'create'])->name('api.v2.auth.signup');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('api.v2.auth.logout');
        Route::post('/delete-account', [RegisterController::class, 'destroy'])->name('api.v2.auth.delete_account');
    });
});

// Contact
Route::prefix('contact')->middleware(['throttle:contact'])->group(function () {
    Route::post('/send', [ContactController::class, 'send'])->name('api.v2.contact.send');
});

// Newsletter
Route::prefix('newsletter')->middleware('throttle:api-auth')->group(function () {
    Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->name('api.v2.newsletter.subscribe');
    Route::post('/resend-verification', [NewsletterController::class, 'resendVerification'])->name('api.v2.newsletter.resend_verification');
    Route::get('/verify/{token}', [NewsletterController::class, 'verify'])->name('api.v2.newsletter.verify');
    Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('api.v2.newsletter.unsubscribe');
    Route::middleware('auth:sanctum')->get('/stats', [NewsletterController::class, 'stats'])->name('api.v2.newsletter.stats');
});

// Users
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('api.v2.user.index');
    Route::post('/', [UserController::class, 'store'])->name('api.v2.user.store');
    Route::get('/{user}', [UserController::class, 'show'])->name('api.v2.user.show');
    Route::put('/{user}', [UserController::class, 'update'])->name('api.v2.user.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('api.v2.user.destroy');
});

// Content
Route::prefix('content')->group(function () {
    Route::get('/{content:slug}/pages/{order}', [ContentController::class, 'page'])->whereNumber('order')->name('api.v2.content.page');
    Route::get('/{content:slug}/pages', [ContentController::class, 'pages'])->name('api.v2.content.pages');
    Route::get('/{content:slug}/related', [ContentController::class, 'related'])->name('api.v2.content.related');
    Route::get('/{platform:slug}/{content:slug}', [ContentController::class, 'show'])->name('api.v2.content.show');
});

// Platforms
Route::prefix('platform')->group(function () {
    Route::get('/', [PlatformController::class, 'index'])->name('api.v2.platform.index');
    Route::get('/{platform:slug}', [PlatformController::class, 'show'])->name('api.v2.platform.show');
    Route::get('/{platform:slug}/featured', [PlatformController::class, 'featured'])->name('api.v2.platform.featured');
    Route::get('/{platform:slug}/categories', [PlatformController::class, 'categories'])->name('api.v2.platform.categories');
    Route::get('/{platform:slug}/content/type/{contentType}', [PlatformController::class, 'contentByType'])->name('api.v2.platform.content_by_type');
});

/*
|--------------------------------------------------------------------------
| Rutas V2 por módulo (archivos individuales)
|--------------------------------------------------------------------------
*/
require __DIR__.'/../airflight/v2.php';
require __DIR__.'/../hardware/v2.php';
require __DIR__.'/../keycounter/v2.php';
require __DIR__.'/../smart_plant/v2.php';
require __DIR__.'/../weather_station/v2.php';
require __DIR__.'/../cv/v2.php';

// Fallback
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API V2 - Endpoint no encontrado',
    ], 404);
});
