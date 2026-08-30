<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\V2\TokenController;
use App\Http\Controllers\Api\Contact\V2\ContactMessageController;
use App\Http\Controllers\Api\Content\V2\ContentController;
use App\Http\Controllers\Api\Newsletter\V2\NewsletterSubscriptionController;
use App\Http\Controllers\Api\Platform\V2\PlatformController;
use App\Http\Controllers\Api\User\V2\UserController;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — rutas generales
|--------------------------------------------------------------------------
|
| Forma REST: el recurso en la URL, la acción en el método HTTP. No hay capa de
| compatibilidad con las URLs anteriores (D112·C1): la v1 sigue desplegada en
| paralelo hasta que cada web se migre.
|
*/

// ── Tokens ───────────────────────────────────────────────────────────────────
//
// El token es un recurso, no un verbo. `POST /auth/login` pasa a ser
// `POST /auth/tokens`, y con eso listar y revocar salen de los métodos HTTP de
// siempre — que es justo lo que necesita el panel de usuario (D90).
//
// El alta de usuarios y la baja de cuenta NO están: se hacen desde Filament.
// `delete-account` además borraba la cuenta y TODOS los tokens sin pedir
// contraseña, así que el token de cualquier cacharro dejaba al dueño fuera
// (auditoría A1). El código sigue escrito y securizado en `RegisterController`.
Route::prefix('auth')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])
        ->middleware('throttle:api-auth')
        ->name('api.v2.auth.tokens.store');

    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::SESSION])->group(function () {
        Route::get('/tokens', [TokenController::class, 'index'])->name('api.v2.auth.tokens.index');
        Route::post('/tokens/devices', [TokenController::class, 'storeDeviceToken'])->name('api.v2.auth.tokens.devices.store');
        Route::delete('/tokens/current', [TokenController::class, 'destroyCurrent'])->name('api.v2.auth.tokens.destroy_current');
        Route::delete('/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token')->name('api.v2.auth.tokens.destroy');
    });
});

// ── Usuarios ─────────────────────────────────────────────────────────────────
//
// Sólo los datos propios. El antiguo `GET /user/{user}` dejaba enumerar
// usuarios con cualquier token (auditoría A4).
Route::prefix('users')->middleware(['auth:sanctum', 'ability:'.TokenAbilities::SESSION])->group(function () {
    Route::get('/me', [UserController::class, 'me'])->name('api.v2.users.me');
});

// ── Mensajes de contacto ─────────────────────────────────────────────────────
//
// El recurso es el mensaje. Se guarda siempre; se reenvía sólo si supera el
// filtro (C4).
Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('api.v2.contact_messages.store');

// ── Newsletter ───────────────────────────────────────────────────────────────
//
// Verificar y darse de baja eran peticiones GET, y esos enlaces viajan dentro
// de un correo: el prefetch de los antivirus y los clientes de correo
// confirmaba suscripciones que nadie había confirmado y daba de baja a quien no
// lo había pedido. El enlace del correo apunta ahora a una página web que no
// muta nada (`GET /newsletter/{token}`, en routes/web.php).
Route::prefix('newsletter/subscriptions')->middleware('throttle:api-auth')->group(function () {
    Route::post('/', [NewsletterSubscriptionController::class, 'store'])
        ->name('api.v2.newsletter.subscriptions.store');

    // Estadísticas: sólo sesión humana de administrador. El gate
    // `view-statistics` existía desde el principio y esta ruta no lo llamaba
    // (auditoría A2). Va antes de {token} para que no se la coma la ruta con
    // parámetro.
    Route::middleware(['auth:sanctum', 'ability:'.TokenAbilities::SESSION, 'can:view-statistics'])
        ->get('/stats', [NewsletterSubscriptionController::class, 'stats'])
        ->name('api.v2.newsletter.subscriptions.stats');

    // Reenvío del correo de verificación. Va por email, no por token: quien lo
    // pide es precisamente quien no tiene el correo con el token.
    Route::post('/verification', [NewsletterSubscriptionController::class, 'resendVerification'])
        ->name('api.v2.newsletter.subscriptions.resend_verification');

    Route::post('/{token}/confirmation', [NewsletterSubscriptionController::class, 'confirm'])
        ->name('api.v2.newsletter.subscriptions.confirm');

    // Baja de un clic (RFC 8058): es la URL de la cabecera `List-Unsubscribe`
    // junto a `List-Unsubscribe-Post`.
    Route::post('/{token}/unsubscription', [NewsletterSubscriptionController::class, 'unsubscribe'])
        ->name('api.v2.newsletter.subscriptions.unsubscribe');

    Route::delete('/{token}', [NewsletterSubscriptionController::class, 'destroy'])
        ->name('api.v2.newsletter.subscriptions.destroy');
});

// ── Contenido y plataformas ──────────────────────────────────────────────────
Route::prefix('platforms')->group(function () {
    Route::get('/', [PlatformController::class, 'index'])->name('api.v2.platforms.index');
    Route::get('/{platform:slug}', [PlatformController::class, 'show'])->name('api.v2.platforms.show');
    Route::get('/{platform:slug}/categories', [PlatformController::class, 'categories'])->name('api.v2.platforms.categories');

    // `featured` y `content/type/{t}` eran rutas propias; ahora son filtros de
    // la colección de contenidos: ?featured=1 y ?type=…
    Route::get('/{platform:slug}/contents', [ContentController::class, 'index'])->name('api.v2.platforms.contents.index');
    Route::get('/{platform:slug}/contents/{content:slug}', [ContentController::class, 'show'])->name('api.v2.platforms.contents.show');
    Route::get('/{platform:slug}/contents/{content:slug}/pages', [ContentController::class, 'pages'])->name('api.v2.platforms.contents.pages');
    Route::get('/{platform:slug}/contents/{content:slug}/pages/{order}', [ContentController::class, 'page'])->whereNumber('order')->name('api.v2.platforms.contents.page');
    Route::get('/{platform:slug}/contents/{content:slug}/related', [ContentController::class, 'related'])->name('api.v2.platforms.contents.related');
});

/*
|--------------------------------------------------------------------------
| Rutas por módulo
|--------------------------------------------------------------------------
*/
require __DIR__.'/../airflight/v2.php';
require __DIR__.'/../hardware/v2.php';
require __DIR__.'/../keycounter/v2.php';
require __DIR__.'/../smart_plant/v2.php';
require __DIR__.'/../weather_station/v2.php';
require __DIR__.'/../cv/v2.php';

/*
|--------------------------------------------------------------------------
| Ruta de cierre
|--------------------------------------------------------------------------
|
| Va para TODOS los métodos, no sólo GET.
|
| `Route::fallback()` sólo registra la ruta en GET. Como su patrón es `.*`,
| cualquier petición que no fuera GET a una ruta inexistente encontraba una
| coincidencia «en otro verbo» y Laravel respondía **405 Method Not Allowed**
| en vez de 404: `POST /api/v2/loquesea` decía «ese recurso existe, prueba con
| otro método», que es mentira. La API no podía devolver un 404 a un POST.
|
| El precio es que un método equivocado sobre una ruta que sí existe también
| responde 404 en lugar de 405. Es el lado bueno del cambio: en esta API el
| contrato es la pareja método + ruta, y lo que no está en la documentación
| sencillamente no existe.
*/
Route::any('{any}', function () {
    return response()->json([
        'success' => false,
        'message' => 'API V2 - Endpoint no encontrado',
    ], 404);
})->where('any', '.*')->name('api.v2.fallback');
