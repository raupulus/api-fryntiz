<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre plantas de SmartPlant.
 *
 * Estaba vacía (sólo el constructor generado). Como `smartplant_registers` no
 * tiene columna `user_id` (N288), la planta es el único sitio donde consta de
 * quién es una lectura: si la propiedad de la planta no se comprueba, no se
 * comprueba nada.
 */
class SmartPlantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    public function delete(User $user, SmartPlantPlant $plant): bool
    {
        return ! TokenAbilities::deviceRequest($user)
            && (int) $plant->user_id === (int) $user->id;
    }

    public function restore(User $user, SmartPlantPlant $plant): bool
    {
        return $this->delete($user, $plant);
    }

    public function forceDelete(User $user, SmartPlantPlant $plant): bool
    {
        return $this->delete($user, $plant);
    }

    /**
     * Escribir una lectura contra esta planta.
     */
    public function writeData(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    /**
     * Pertenencia de la planta y, si el token está ligado a un dispositivo,
     * que la planta cuelgue de ese dispositivo.
     */
    private function isOwnedBy(User $user, SmartPlantPlant $plant): bool
    {
        if ((int) $plant->user_id !== (int) $user->id) {
            return false;
        }

        if ($plant->hardware_device_id === null) {
            return ! TokenAbilities::deviceRequest($user);
        }

        return TokenAbilities::tokenReachesDevice($user, (int) $plant->hardware_device_id);
    }
}
