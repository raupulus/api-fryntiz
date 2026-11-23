<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre plataformas (las webs que consumen la API).
 *
 * Crear, editar y borrar plataformas es cosa de administración (respetando la
 * frontera Admin vs SuperAdmin). Un editor ve únicamente las que tiene
 * asignadas en `platform_user`.
 */
class PlatformPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function view(User $user, Platform $platform): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        if ($user->isAdmin()) {
            return $this->adminCanManageOwner($user, $platform->user_id !== null ? (int) $platform->user_id : null);
        }

        return $user->canManagePlatform((int) $platform->id);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function update(User $user, Platform $platform): bool
    {
        return $this->adminCanManageOwner($user, $platform->user_id !== null ? (int) $platform->user_id : null);
    }

    public function delete(User $user, Platform $platform): bool
    {
        return $this->adminCanManageOwner($user, $platform->user_id !== null ? (int) $platform->user_id : null);
    }

    public function restore(User $user, Platform $platform): bool
    {
        return $this->adminCanManageOwner($user, $platform->user_id !== null ? (int) $platform->user_id : null);
    }

    public function forceDelete(User $user, Platform $platform): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
