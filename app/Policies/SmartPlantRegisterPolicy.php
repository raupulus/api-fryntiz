<?php

declare(strict_types=1);

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

    /**
     * Los registros los sube el propio dispositivo IoT; no tiene sentido que
     * alguien cree uno a mano desde el panel, ni admin ni superadmin.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Son lecturas de sensores, no datos que se corrijan a mano: editar un
     * registro falsearía el histórico sin dejar rastro de que se tocó.
     */
    public function update(User $user, SmartPlantRegister $register): bool
    {
        return false;
    }

    public function delete(User $user, SmartPlantRegister $register): bool
    {
        return $this->isAdmin($user);
    }
}
