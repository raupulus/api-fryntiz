<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\User\UserProfileController;
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

/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/

######################################################
##                   Autenticación
######################################################

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [LoginController::class, 'login'])->middleware(\Illuminate\Session\Middleware\StartSession::class);
    Route::post('signup', [RegisterController::class, 'register']);

    //Route::post('/register', [AuthController::class, 'register']);
    //Route::post('/login', [AuthController::class, 'login']);

    /*
    Route::post('/tokens/create', function (Request $request) {
        $token = $request->user()->createToken($request->token_name);

        return ['token' => $token->plainTextToken];
    });
    */

    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/logout', 'AuthController@logout');
    });
});

######################################################
##                   Usuarios
######################################################
Route::group(['prefix' => 'user','middleware' => 'auth:sanctum'], function () {
    ## Datos del perfil para el usuario logueado.
    Route::get('/profile', [UserProfileController::class, 'myProfile']);

    ## Datos del perfil de un usuario.
    Route::get('/profile/{user_id}', [UserProfileController::class, 'show']);
});


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function(){
    return response()->json(['message' => 'Page Not Found'], 404);
});
