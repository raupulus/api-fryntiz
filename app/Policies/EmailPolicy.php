<?php

namespace App\Policies;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class PlatformPolicy
 */
class EmailPolicy
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
        return false;
    }

    public function store(User $user)
    {
        return false;
    }

    public function delete(User $user, Platform $platform)
    {
        return false;
        //return $platform->user_id === $user->id;
    }

    public function show(User $user, Platform $platform)
    {
        return false;
    }

    public function update(User $user, Platform $platform)
    {
        return false;
    }
}
