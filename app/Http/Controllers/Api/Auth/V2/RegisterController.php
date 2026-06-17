<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\V2\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de registro de usuarios para API V2.
 */
class RegisterController extends BaseApiController
{
    /**
     * Crea una nueva cuenta de usuario.
     */
    public function create(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 3,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->createdResponse([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Registro exitoso');
    }

    /**
     * Elimina la cuenta del usuario autenticado.
     */
    public function destroy(): JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->delete();
        $user->delete();

        return $this->successResponse(message: 'Cuenta eliminada correctamente');
    }
}
