<?php

use App\Http\Controllers\Api\Auth\V1\LoginController;
use App\Http\Controllers\Api\Auth\V1\RegisterController;
use App\Http\Controllers\Api\User\V1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\Content\ContentController;
use App\Http\Controllers\Api\Content\ContentCategoryController;
use App\Http\Controllers\Api\NewsletterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

######################################################
##                   Autenticación
######################################################
Route::group(['prefix' => 'v1/auth'], function () {
    ## Ruta para obtener token csrf.
    Route::get('/csrf-cookie', [LoginController::class, 'csrfCookie'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.csrf_cookie');

    ## Ruta para hacer login.
    Route::post('/login', [LoginController::class, 'login'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.login');

    ## Ruta para crear un nuevo registro de usuario.
    Route::post('/signup', [RegisterController::class, 'create'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.signup');

    ## Grupo de rutas protegidas por token de autenticación.
    Route::group(['middleware' => 'auth:sanctum'], function () {
        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/logout', [LoginController::class, 'logout'])
            ->name('api.v1.auth.logout');

        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/delete-account', [RegisterController::class, 'destroy'])
            ->name('api.v1.auth.delete_account');
    });
});

######################################################
##                   Contacto
######################################################

Route::group(['prefix' => 'v1/contact', 'middleware' => ['ip.counter.strict']], function () {
    ## Recibe un formulario.
    Route::post('/send', [\App\Http\Controllers\EmailController::class, 'sendFromForm'])
        ->name('api.v1.contact.send');
});


######################################################
##                   Newsletter
######################################################
Route::prefix('v1/newsletter')->group(function () {
    Route::post('subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('resend-verification', [NewsletterController::class, 'resendVerification']);
    Route::get('verify/{token}', [NewsletterController::class, 'verify'])->name('newsletter.verify');
    Route::get('unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    Route::get('stats', [NewsletterController::class, 'stats']);
});

######################################################
##                   Usuarios
######################################################
Route::group(['prefix' => 'v1/user', 'middleware' => 'auth:sanctum'], function () {
    ## Devuelve todos los usuarios.
    Route::get('/index', [UserController::class, 'index'])
        ->name('api.v1.user.index');

    ## Devuelve un usuario en específico.
    Route::post('/show', [UserController::class, 'show'])
        ->name('api.v1.user.show');

    ## Crear un nuevo usuario.
    Route::post('/create', [UserController::class, 'create'])
        ->name('api.v1.user.create');

    ## Actualizar un usuario.
    Route::put('/update', [UserController::class, 'update'])
        ->name('api.v1.user.update');

    ## Eliminar un usuario.
    Route::delete('/delete', [UserController::class, 'destroy'])
        ->name('api.v1.user.delete');
});


######################################################
##                   Contenidos
######################################################
Route::group(['prefix' => 'v1/content', 'middleware' => ['check.domain', 'cors']], function () {
    ## Devuelve un contenido a partir de su slug
    Route::get('/{platform:slug}/{content:slug}/get', [ContentController::class, 'getContentBySlug']);

    ## Devuelve todas las páginas asociadas a un contenido.
    Route::get('/{content:slug}/get/pages', [ContentController::class, 'index']);

    ## Devuelve una página concreta para un contenido.
    Route::get('/{content:slug}/get/page/{page:order}/{type?}', [ContentController::class, 'show']);

    ## Contenido relacionado al contenido recibido por el slug
    Route::get('/{content:slug}/get/related', [ContentController::class, 'relatedContent']);
});


######################################################
##                   Plataformas
######################################################
Route::group(['prefix' => 'v1/platform', 'middleware' => ['check.domain', 'cors']], function () {

    ## Devuelve información principal de todas las plataformas existentes.
    Route::get('/all', [PlatformController::class, 'index']);

    ## Devuelve toda la información para una plataforma concreta.
    Route::get('/{platform:slug}/info', [PlatformController::class, 'info']);

    ## Devuelve el contenido asociado a una plataforma para un tipo de contenido concreto
    Route::get('/{platform:slug}/content/type/{contentType}', [PlatformController::class, 'getContentByType']);

    ## Devuelve el contenido destacado y últimos añadidos para la plataforma
    Route::get('/{platform:slug}/content/featured', [PlatformController::class, 'getContentFeatured']);

    ## Devuelve un listado de categorías para una plataforma
    Route::get('/{platform:slug}/get/categories', [ContentCategoryController::class, 'index']);
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function () {
    return response()->json(['message' => 'Page Not Found'], 404);
});
