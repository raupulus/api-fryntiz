<?php

namespace App\Http\Controllers\Api\Auth\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonHelper;
use function response;

/**
 * Class LoginController
 */
class LoginController extends Controller
{
    /**
     * Loguea un usuario.
     *
     * @param \App\Http\Requests\Api\Auth\LoginRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $request->validated();

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            //'token' => \auth()->user()->createToken($request->device)->plainTextToken,
            'token' => \auth()->user()->createToken('login')->plainTextToken,
            'message' => 'Success'
        ]);
    }

    /**
     * Desloguea un usuario.
     *
     * @param \App\Http\Requests\Api\Auth\LogoutRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(LogoutRequest $request)
    {
        $request->user()->currentAccessToken()->delete();

        return JsonHelper::success(['message' => 'Successfully logged out']);
    }
}
