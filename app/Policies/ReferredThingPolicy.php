<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hardware\HardwareDevice;
use App\Models\Referred\ReferredThing;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre enlaces de compra de afiliados.
 *
 * Un enlace de compra pertenece al hardware del que forma parte. Puede gestionarlo
 * el propietario del dispositivo hardware o un administrador con sesión (sin
 * cruzar la frontera Admin → SuperAdmin).
 */
class ReferredThingPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
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
        return $this->isHumanSuperAdmin($user);
    }

    private function canManage(User $user, ReferredThing $thing): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        $rawOwnerId = $thing->relationLoaded('device')
            ? $thing->device?->user_id
            : HardwareDevice::query()->whereKey($thing->hardware_device_id)->value('user_id');

        $ownerId = $rawOwnerId !== null ? (int) $rawOwnerId : null;

        if ($ownerId !== null && $ownerId === (int) $user->id) {
            return true;
        }

        return $this->adminCanManageOwner($user, $ownerId);
    }
}
