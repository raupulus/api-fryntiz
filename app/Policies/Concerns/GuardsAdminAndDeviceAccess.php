<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Support\Auth\TokenAbilities;

/**
 * Reglas comunes de seguridad en Policies:
 *
 *  1. Ningún token IoT o de módulo (`TokenAbilities::deviceRequest()`) hereda
 *     privilegios de administración o edición (Trampa 5).
 *  2. Un `Admin` humano gestiona todos los recursos salvo los propiedad de un
 *     `SuperAdmin` (`AGENTS.md` §7).
 */
trait GuardsAdminAndDeviceAccess
{
    protected function isHumanAdmin(User $user): bool
    {
        return $user->isAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    protected function isHumanSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    protected function isHumanEditor(User $user): bool
    {
        return $user->isEditor() && ! TokenAbilities::deviceRequest($user);
    }

    protected function adminCanManageOwner(User $actor, ?int $ownerId): bool
    {
        if (! $this->isHumanAdmin($actor)) {
            return false;
        }

        if ($actor->isSuperAdmin() || $ownerId === null || $ownerId === (int) $actor->id) {
            return true;
        }

        return ! User::query()
            ->whereKey($ownerId)
            ->where('role_id', UserRoleEnum::SuperAdmin->value)
            ->exists();
    }
}
