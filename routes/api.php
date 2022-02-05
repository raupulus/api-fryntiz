<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
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

Route::group([
    'prefix' => 'auth'
],
    function () {
        Route::post('/login', [LoginController::class, 'login']);
        Route::post('signup', [RegisterController::class, 'register']);

//Route::post('/register', [AuthController::class, 'register']);
//Route::post('/login', [AuthController::class, 'login']);

        /*
        Route::post('/tokens/create', function (Request $request) {
            $token = $request->user()->createToken($request->token_name);

            return ['token' => $token->plainTextToken];
        });
        */

        Route::group([
            'middleware' => 'auth:sanctum'
        ], function() {
            Route::get('/logout', 'AuthController@logout');


            Route::get('/user', function () {
                return response()->json(auth()->user());
            });
        });
    }
);
