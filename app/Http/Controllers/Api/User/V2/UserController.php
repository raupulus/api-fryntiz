<?php

namespace App\Http\Controllers\Api\User\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\User\V2\StoreUserRequest;
use App\Http\Requests\Api\User\V2\UpdateUserRequest;
use App\Http\Resources\V2\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de usuarios para API V2.
 */
class UserController extends BaseApiController
{
    /**
     * Lista usuarios paginados (solo admin/superadmin).
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::paginate(15);

        return UserResource::collection($users)->response();
    }

    /**
     * Crea un nuevo usuario (solo admin/superadmin).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $this->createdResponse(
            new UserResource($user),
            'Usuario creado correctamente'
        );
    }

    /**
     * Muestra un usuario específico.
     */
    public function show(User $user): JsonResponse
    {
        return $this->successResponse(new UserResource($user));
    }

    /**
     * Actualiza un usuario (requiere autorización).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return $this->successResponse(
            new UserResource($user->fresh()),
            'Usuario actualizado correctamente'
        );
    }

    /**
     * Elimina un usuario (requiere autorización).
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->tokens()->delete();
        $user->delete();

        return $this->successResponse(message: 'Usuario eliminado correctamente');
    }
}
