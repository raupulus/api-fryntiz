<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform;
use App\Models\PlatformEditorProfile;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Los perfiles de editor se gestionan en la ficha de la plataforma, así que
 * los edita quien edita la plataforma (`PlatformPolicy::update`): administración
 * humana respetando la frontera Admin vs SuperAdmin.
 */
class PlatformEditorProfilePolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function view(User $user, PlatformEditorProfile $profile): bool
    {
        return $this->canManagePlatformOwner($user, $profile);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function update(User $user, PlatformEditorProfile $profile): bool
    {
        return $this->canManagePlatformOwner($user, $profile);
    }

    public function delete(User $user, PlatformEditorProfile $profile): bool
    {
        return $this->canManagePlatformOwner($user, $profile);
    }

    private function canManagePlatformOwner(User $user, PlatformEditorProfile $profile): bool
    {
        $ownerId = $profile->relationLoaded('platform')
            ? $profile->platform?->user_id
            : Platform::query()->whereKey($profile->platform_id)->value('user_id');

        return $this->adminCanManageOwner($user, $ownerId !== null ? (int) $ownerId : null);
    }
}
