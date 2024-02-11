<?php

namespace App\Policies;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class CategoryPolicy
 */
class TechnologyPolicy
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

    public function delete(User $user, Technology $category)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $category->user_id === $user->id;
    }

    public function show(User $user, Technology $tag)
    {
        return true;
    }

    public function update(User $user, Technology $category)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $category->user_id === $user->id;
    }
}
