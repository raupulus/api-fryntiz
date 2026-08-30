<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KeyCounter\Mouse;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre sesiones de mouse de KeyCounter.
 *
 * Todos los métodos salvo `create` estaban vacíos —cuerpo `//`, que devuelve
 * null y para el Gate significa denegar—. Mientras la policy no se descubría no
 * pasaba nada; en cuanto se registra, un administrador se quedaba fuera de su
 * propio listado. Ahora dicen lo que tienen que decir: cada registro es de
 * quien lo generó.
 */
class KeyCounterMousePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function view(User $user, Mouse $mouse): bool
    {
        return $this->isOwnedBy($user, $mouse);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Mouse $mouse): bool
    {
        return $this->isOwnedBy($user, $mouse);
    }

    public function delete(User $user, Mouse $mouse): bool
    {
        return $this->isOwnedBy($user, $mouse);
    }

    public function restore(User $user, Mouse $mouse): bool
    {
        return $this->isOwnedBy($user, $mouse);
    }

    public function forceDelete(User $user, Mouse $mouse): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * Pertenencia por usuario y, si el token está ligado a un dispositivo, que
     * el registro venga de ese dispositivo.
     */
    private function isOwnedBy(User $user, Mouse $mouse): bool
    {
        if ((int) $mouse->user_id !== (int) $user->id && ! $user->isAdmin()) {
            return false;
        }

        if ($mouse->hardware_device_id === null) {
            return ! TokenAbilities::deviceRequest($user);
        }

        return TokenAbilities::tokenReachesDevice($user, (int) $mouse->hardware_device_id);
    }
}
