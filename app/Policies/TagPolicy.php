<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre etiquetas del CMS.
 */
class TagPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function view(User $user, Tag $tag): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function update(User $user, Tag $tag): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function restore(User $user, Tag $tag): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
