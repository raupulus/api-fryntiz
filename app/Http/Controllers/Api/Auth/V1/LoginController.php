<?php

namespace App\Http\Controllers\Api\Auth\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use Illuminate\Http\JsonResponse;
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
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->validated();

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            // 'token' => \auth()->user()->createToken($request->device)->plainTextToken,
            'token' => \auth()->user()->createToken('login')->plainTextToken,
            'message' => 'Success',
        ]);
    }

    /**
     * Desloguea un usuario.
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return JsonHelper::success(['message' => 'Successfully logged out']);
    }

    /**
     * Genera un token csrf, lo añade como cookie a la respuesta y cuerpo para devolverlo.
     */
    public function csrfCookie(): JsonResponse
    {
        $csrfToken = \request()?->cookie('XSRF-TOKEN') ?? csrf_token();

        return JsonHelper::success([
            'csrf_token' => $csrfToken,
        ])->cookie('XSRF-TOKEN', $csrfToken);
    }
}
