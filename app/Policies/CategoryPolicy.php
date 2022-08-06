<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use RoleHelper;

/**
 * Class CategoryPolicy
 */
class CategoryPolicy
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

    public function delete(User $user, Category $category)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $category->user_id === $user->id;
    }

    public function show(User $user, Category $tag)
    {
        return true;
    }

    public function update(User $user, Category $category)
    {
        return RoleHelper::isAdmin($user->role_id);
        //return $category->user_id === $user->id;
    }
}
