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
}
