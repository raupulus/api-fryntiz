<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth\V2;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Auth\V2\DeleteAccountRequest;
use App\Http\Requests\Api\Auth\V2\RegisterRequest;
use App\Http\Resources\V2\UserResource;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Registro y baja de cuenta para API V2.
 *
 * ┌───────────────────────────────────────────────────────────────────────────┐
 * │ RUTAS DESACTIVADAS A PROPÓSITO                                            │
 * │                                                                           │
 * │ El alta de usuarios NO se hace por la API: se hace desde el panel de      │
 * │ Filament por un administrador. Tampoco hay baja de cuenta por la API.     │
 * │ Las rutas están comentadas en `routes/api/v2.php`; el código se conserva  │
 * │ escrito y securizado por si algún día se abre el registro.                │
 * │                                                                           │
 * │ Motivo de la baja (auditoría A1): `POST /auth/delete-account` borraba la  │
 * │ cuenta y TODOS los tokens del usuario sin pedir contraseña y sin          │
 * │ comprobar abilities, así que el token de un cacharro en la montaña podía  │
 * │ dejar al dueño fuera y obligar a re-flashear todo el parque.              │
 * └───────────────────────────────────────────────────────────────────────────┘
 */
class RegisterController extends BaseApiController
{
    /**
     * Crea una nueva cuenta de usuario.
     *
     * Ruta desactivada. El rol se fija aquí, nunca llega del cliente.
     */
    public function create(RegisterRequest $request): JsonResponse
    {
        $user = new User;
        $user->forceFill([
            'name' => $request->validated('name'),
            'nickname' => $request->validated('nickname'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
            'email_verified_at' => null,
        ])->save();

        $token = $user->createToken(
            'api-session',
            TokenAbilities::forSession(),
            now()->addDays((int) config('auth.api_session_days', 30))
        )->plainTextToken;

        return $this->createdResponse([
            'token' => $token,
            'user' => new UserResource($user->loadMissing('role')),
        ], 'Registro exitoso');
    }

    /**
     * Elimina la cuenta del usuario autenticado.
     *
     * Ruta desactivada. Si se reactivase: exige contraseña actual
     * ({@see DeleteAccountRequest}) y la ruta debe llevar
     * `ability:session`, para que ningún token de dispositivo la alcance.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return $this->successResponse(message: 'Cuenta eliminada correctamente');
    }
}
