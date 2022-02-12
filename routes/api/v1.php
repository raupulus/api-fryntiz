<?php

use App\Http\Controllers\Api\Auth\V1\LoginController;
use App\Http\Controllers\Api\Auth\V1\RegisterController;
use App\Http\Controllers\Api\User\V1\UserController;
use App\Http\Controllers\Api\User\V1\UserProfileController;
use Illuminate\Support\Facades\Route;

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
    ## Ruta para hacer login.
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.login');

    ## Ruta para crear un nuevo registro de usuario.
    Route::post('/signup', [RegisterController::class, 'create'])
        //->middleware(\Illuminate\Session\Middleware\StartSession::class)
        ->name('api.v1.auth.signup');

    ## Grupo de rutas protegidos por token de autenticación.
    Route::group(['middleware' => 'auth:sanctum'], function () {
        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/logout', [LoginController::class, 'logout'])
            ->name('api.v1.auth.logout');

        ## Cierra la sesión de un usuario e invalida su token actual.
        Route::post('/delete-account', [RegisterController::class, 'destroy'])
            ->name('api.v1.auth.delete_account');
    });

    /*
    Route::post('/tokens/create', function (Request $request) {
        $token = $request->user()->createToken($request->token_name);

        return ['token' => $token->plainTextToken];
    });
    */
});

######################################################
##                   Usuarios
######################################################
Route::group(['prefix' => 'v1/user','middleware' => 'auth:sanctum'], function
() {
    ## Devuelve todos los usuarios.
    Route::get('/index', [UserController::class, 'index'])
        ->name('api.v1.user.index');

    ## Datos del perfil para el usuario logueado.
    Route::get('/profile', [UserProfileController::class, 'myProfile'])
        ->name('api.v1.user.profile');

    ## Datos del perfil de un usuario.
    Route::get('/profile/{user_id}', [UserProfileController::class, 'show'])
        ->name('api.v1.user.profile');
});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
