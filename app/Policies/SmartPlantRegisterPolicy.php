<?php

namespace App\Policies;

use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class SmartPlantRegisterPolicy
 */
class SmartPlantRegisterPolicy
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

    protected function isAdmin(User $user): bool
    {
        return $user->role && in_array($user->role->slug, ['admin', 'superadmin'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, SmartPlantRegister $register): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, SmartPlantRegister $register): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, SmartPlantRegister $register): bool
    {
        return $this->isAdmin($user);
    }
}
