<?php

use App\Http\Controllers\Api\Auth\V1\LoginController;
use App\Http\Controllers\Api\Auth\V1\RegisterController;
use App\Http\Controllers\Api\User\V1\UserController;
use App\Models\CV\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlatformController;
use App\Http\Controllers\Api\Content\ContentController;

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
    ## Devuelve todas las páginas asociadas a un contenido.
    Route::get('/{content:slug}/get/pages', [ContentController::class, 'index']);

    ## Devuelve una página concreta para un contenido.
    Route::get('/{content:slug}/get/page/{page:order}/{type?}', [ContentController::class, 'show']);
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
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function () {
    return response()->json(['message' => 'Page Not Found'], 404);
});
