<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiProvider;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Los proveedores de IA, con sus claves, son sólo del SuperAdmin humano:
 * ni un administrador ni un token de dispositivo IoT pueden acceder a ellos.
 */
class AiProviderPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanSuperAdmin($user);
    }

    public function view(User $user, AiProvider $provider): bool
    {
        return $this->isHumanSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanSuperAdmin($user);
    }

    public function update(User $user, AiProvider $provider): bool
    {
        return $this->isHumanSuperAdmin($user);
    }

    public function delete(User $user, AiProvider $provider): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
