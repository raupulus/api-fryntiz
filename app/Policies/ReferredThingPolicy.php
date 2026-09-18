<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Referred\ReferredThing;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre enlaces de compra de afiliados.
 *
 * Un enlace de compra pertenece al hardware del que forma parte. Puede gestionarlo
 * el propietario del dispositivo hardware o un administrador con sesión.
 */
class ReferredThingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReferredThing $thing): bool
    {
        return $this->canManage($user, $thing);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, ReferredThing $thing): bool
    {
        return $this->canManage($user, $thing);
    }

    public function delete(User $user, ReferredThing $thing): bool
    {
        return $this->canManage($user, $thing);
    }

    public function restore(User $user, ReferredThing $thing): bool
    {
        return $this->canManage($user, $thing);
    }

    public function forceDelete(User $user, ReferredThing $thing): bool
    {
        return $this->canManage($user, $thing);
    }

    private function canManage(User $user, ReferredThing $thing): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $device = $thing->device;

        return $device !== null && (int) $device->user_id === (int) $user->id;
    }
}
