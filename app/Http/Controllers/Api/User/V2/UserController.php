<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Usuarios en la API V2.
 *
 * Sólo `GET /users/me`. La gestión de usuarios (alta, edición, contraseña,
 * borrado) vive exclusivamente en el panel de Filament.
 *
 * Antes existían `index`, `store`, `show({user})`, `update` y `destroy`.
 * `show({user})` no comprobaba nada: cualquier token —incluido el de una
 * estación meteorológica— podía enumerar usuarios (auditoría A4). No se
 * sustituyen por versiones autorizadas porque la API no gestiona usuarios.
 */
class UserController extends BaseApiController
{
    /**
     * Datos del dueño del token con el que se llama.
     *
     * No acepta ningún identificador: siempre es el propio usuario.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->loadMissing('role'))
        );
    }
}
