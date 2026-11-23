<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy de registros de sensores de SmartPlant.
 */
class SmartPlantRegisterPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function view(User $user, SmartPlantRegister $register): bool
    {
        return $this->isHumanAdmin($user);
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
        $ownerId = $register->relationLoaded('plant')
            ? $register->plant?->user_id
            : SmartPlantPlant::query()->whereKey($register->plant_id)->value('user_id');

        return $this->adminCanManageOwner($user, $ownerId !== null ? (int) $ownerId : null);
    }
}
