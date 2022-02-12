<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class UserPolicy
 * @package App\Policies
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function create(User $user)
    {
        // TODO → Crear sistema de permisos

        //return $user->hasPermissionTo('create-user');

        // TOFIX → Temparlmente solo permito crear usuarios al admin.
        return $user->role_id === 1;
    }

    /**
     * Permisos para ver usuario
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $model
     *
     * @return bool
     */
    public function view(User $user, User $model)
    {
        // TODO → Crear sistema de permisos

        //return $user->hasPermissionTo('view-user');

        if ($user->id === $model->id) {
            return true;
        }

        if ($user->role_id === 1) {
            return true;
        }

        return true;
    }

    /**
     * Permisos para editar usuario.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $model
     *
     * @return bool
     */
    public function update(User $user, User $model)
    {
        // TODO → Crear sistema de permisos

        //return $user->hasPermissionTo('update-user');

        ## Es el propio usuario
        if ($user->id === $model->id) {
            return true;
        }

        ## Es el admin
        if ($user->role_id === 1) {
            return true;
        }

        return false;
    }
}
