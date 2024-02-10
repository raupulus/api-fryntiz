<?php

namespace App\Http\Controllers\Api\Auth\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JsonHelper;
use function route;

/**
 * Class RegisterController
 *
 * @package App\Http\Controllers\Api\Auth
 */
class RegisterController extends Controller
{
    /**
     * Create a new user instance after a valid registration.
     *
     * @param RegisterRequest $request
     *
     * @return JsonResponse
     */
    public function create(RegisterRequest $request): JsonResponse
    {
        ## Bloqueo registro de usuarios hasta tener roles definidos en toda la aplicación
        return JsonHelper::forbidden();

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        $newToken = $user->createToken('login');

        return JsonHelper::success([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $newToken->plainTextToken,
        ]);
    }

    /**
     * Elimina la cuenta de un usuario.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {

        // TODO → No puede quedar tan simple eliminar una cuenta
        // TODO → Generar un número, enviarlo por correo y validarlo
        // TODO → O enviar un número que sea recibido en esta función

        return JsonHelper::success([
            'message' => 'TODO → No se permiten eliminar usuarios',
        ]);

        $user = $request->user();

        ## Elimino tokens.
        $user->tokens()->delete();

        ## Elimino usuario y todos sus datos asociados.
        $deleted = $user->safeDelete();

        if ($deleted) {
            return JsonHelper::success([
                'message' => 'Successfully deleted user account along with all its associated data',
                'redirect_url' => route('api.v1.auth.login'),
            ]);
        }

        return JsonHelper::failed('Error deleting user account, contact the administrator',);
    }
}
