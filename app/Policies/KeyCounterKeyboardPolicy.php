<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KeyCounter\Keyboard;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre sesiones de keyboard de KeyCounter.
 *
 * Todos los métodos salvo `create` estaban vacíos —cuerpo `//`, que devuelve
 * null y para el Gate significa denegar—. Mientras la policy no se descubría no
 * pasaba nada; en cuanto se registra, un administrador se quedaba fuera de su
 * propio listado. Ahora dicen lo que tienen que decir: cada registro es de
 * quien lo generó.
 */
class KeyCounterKeyboardPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function view(User $user, Keyboard $keyboard): bool
    {
        return $this->isOwnedBy($user, $keyboard);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Keyboard $keyboard): bool
    {
        return $this->isOwnedBy($user, $keyboard);
    }

    public function delete(User $user, Keyboard $keyboard): bool
    {
        return $this->isOwnedBy($user, $keyboard);
    }

    public function restore(User $user, Keyboard $keyboard): bool
    {
        return $this->isOwnedBy($user, $keyboard);
    }

    public function forceDelete(User $user, Keyboard $keyboard): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * Pertenencia por usuario y, si el token está ligado a un dispositivo, que
     * el registro venga de ese dispositivo.
     */
    private function isOwnedBy(User $user, Keyboard $keyboard): bool
    {
        $ownerId = $keyboard->user_id !== null ? (int) $keyboard->user_id : null;

        if (TokenAbilities::deviceRequest($user)) {
            if ($ownerId === null || $ownerId !== (int) $user->id || $keyboard->hardware_device_id === null) {
                return false;
            }

            return TokenAbilities::tokenReachesDevice($user, (int) $keyboard->hardware_device_id);
        }

        if ($ownerId !== null && $ownerId === (int) $user->id) {
            return true;
        }

        return $this->adminCanManageOwner($user, $ownerId);
    }
}
