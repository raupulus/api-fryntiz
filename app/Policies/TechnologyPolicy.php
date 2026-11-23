<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Technology;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre tecnologías del CMS.
 */
class TechnologyPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function view(User $user, Technology $technology): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function update(User $user, Technology $technology): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function delete(User $user, Technology $technology): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function restore(User $user, Technology $technology): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function forceDelete(User $user, Technology $technology): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
