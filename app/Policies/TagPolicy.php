<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class TagPolicy
 */
class TagPolicy
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

    public function delete(User $user, Tag $tag)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $tag->user_id === $user->id;
    }

    public function show(User $user, Tag $tag)
    {
        return true;
    }

    public function update(User $user, Tag $tag)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $tag->user_id === $user->id;
    }
}
