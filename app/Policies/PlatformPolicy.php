<?php

namespace App\Policies;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class PlatformPolicy
 */
class PlatformPolicy
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

    public function index(User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return RoleHelper::isAdmin($user->role_id);
    }

    public function store(User $user)
    {
        return RoleHelper::isAdmin($user->role_id);
    }

    public function delete(User $user, Platform $platform)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $platform->user_id === $user->id;
    }

    public function show(User $user, Platform $platform)
    {
        return true;
    }

    public function update(User $user, Platform $platform)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $platform->user_id === $user->id;
    }
}
