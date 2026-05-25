<?php

namespace App\Http\Controllers\Api\Auth\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\V2\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de autenticación (login/logout) para API V2.
 */
class LoginController extends BaseApiController
{
    /**
     * Inicia sesión y genera token Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Credenciales invalidas', 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Inicio de sesion exitoso');
    }

    /**
     * Cierra la sesión eliminando el token actual.
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return $this->successResponse(message: 'Sesion cerrada correctamente');
    }
}
