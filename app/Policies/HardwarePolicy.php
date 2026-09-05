<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre dispositivos hardware.
 *
 * Un dispositivo es de un usuario y sólo de él. Además, si la petición llega
 * con un token ligado a un dispositivo concreto (`device:{id}`), ese token no
 * alcanza a los demás dispositivos del mismo dueño: es la diferencia entre
 * robar un cacharro y robar la cuenta entera.
 *
 * Ojo: el atajo `Gate::before` de superadmin está desactivado para tokens de
 * dispositivo (ver `AppServiceProvider`), así que estos métodos sí se ejecutan
 * cuando quien llama es un cacharro.
 */
class HardwarePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HardwareDevice $device): bool
    {
        return $this->isOwnedAndReachable($user, $device);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, HardwareDevice $device): bool
    {
        return $this->isOwnedAndReachable($user, $device);
    }

    public function delete(User $user, HardwareDevice $device): bool
    {
        // Borrar un dispositivo no es tarea de un dispositivo.
        return ! TokenAbilities::deviceRequest($user)
            && ((int) $device->user_id === (int) $user->id || $user->isAdmin());
    }

    public function restore(User $user, HardwareDevice $device): bool
    {
        return $this->delete($user, $device);
    }

    public function forceDelete(User $user, HardwareDevice $device): bool
    {
        return $this->delete($user, $device);
    }

    /**
     * Escribir lecturas (energía, carga solar, estado) contra un dispositivo.
     */
    public function writeData(User $user, HardwareDevice $device): bool
    {
        return $this->isOwnedAndReachable($user, $device);
    }

    /**
     * Pertenencia + ligado del token al dispositivo concreto.
     *
     * ## El administrador también llega (AR-SEC-03)
     *
     * `Gate::before` sólo regala el paso a `SuperAdmin`. Un `Admin` legítimo se
     * llevaba un 403 al abrir en `/admin` un dispositivo de otro usuario: la
     * tabla lo listaba y la ficha no abría.
     *
     * El bypass es para administradores **con sesión**. Para un token de
     * dispositivo se mantiene la regla estricta —dueño y token ligado a ese
     * cacharro—, porque el dueño de los cacharros es precisamente `SuperAdmin`
     * y un `|| $user->isAdmin()` sin condiciones convertiría el token de una
     * estación meteorológica en una llave para todo el parque de hardware.
     */
    private function isOwnedAndReachable(User $user, HardwareDevice $device): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return (int) $device->user_id === (int) $user->id
                && TokenAbilities::tokenReachesDevice($user, (int) $device->id);
        }

        return (int) $device->user_id === (int) $user->id || $user->isAdmin();
    }
}
