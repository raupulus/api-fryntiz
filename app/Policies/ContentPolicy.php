<?php

namespace App\Policies;

use App\Models\Content\Content;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class TagPolicy
 */
class ContentPolicy
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

    public function delete(User $user, Content $content)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $content->user_id === $user->id;
    }

    public function show(User $user, Content $tag)
    {
        return true;
    }

    public function update(User $user, Content $content)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $content->user_id === $user->id;
    }
}
