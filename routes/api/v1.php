<?php

use App\Http\Controllers\Api\Auth\V1\LoginController;
use App\Http\Controllers\Api\Auth\V1\RegisterController;
use App\Http\Controllers\Api\User\V1\UserController;
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
##                   Plataformas
######################################################
Route::group(['prefix' => 'v1/platform', 'middleware' => ['cors']], function () {
    Route::post('/info', function () {
        return response()->json([
            'platforms' => [
                [
                    'name' => 'My Api',
                    'url' => 'https://api.fryntiz.dev',
                    'icon' => ''

                ],
                [
                    'name' => 'Curriculum',
                    'url' => 'https://curriculum.fryntiz.dev',
                    'icon' => ''

                ],
                [
                    'name' => 'La Guía Linux',
                    'url' => 'https://laguialinux.es',
                    'icon' => ''

                ],
            ],
            'technologies' => [
                'vue',
                'tailwind'
            ],
            'resources' => [

                [
                    'name' => 'Gitlab',
                    'url' => 'https://gitlab.com/fryntiz/www.fryntiz.es',
                    'icon' => ''

                ],
                [
                    'name' => 'Github',
                    'url' => 'https://github.com/fryntiz/www.fryntiz.es',
                    'icon' => ''

                ],
            ],
            'pages' => [
                [
                    'title' => 'Link1',
                    'url' => 'https://www.fryntiz.es',
                ],
                [
                    'title' => 'Link2',
                    'url' => 'https://www.fryntiz.es',
                ],
            ],
            'message' => 'Hola soy un mensaje de prueba',
            'name' => 'Laravel',
            'version' => '8.x',
            'author' => 'Taylor Otwell',
        ]);
    });
});

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function () {
    return response()->json(['message' => 'Page Not Found'], 404);
});
