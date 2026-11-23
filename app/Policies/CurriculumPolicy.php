<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CV\Curriculum;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use App\Support\Auth\TokenAbilities;

class CurriculumPolicy
{
    use GuardsAdminAndDeviceAccess;

    public function viewAny(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function view(User $user, Curriculum $curriculum): bool
    {
        return $this->canManage($user, $curriculum);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        return $this->canManage($user, $curriculum);
    }

    public function delete(User $user, Curriculum $curriculum): bool
    {
        return $this->canManage($user, $curriculum);
    }

    public function restore(User $user, Curriculum $curriculum): bool
    {
        return $this->canManage($user, $curriculum);
    }

    public function forceDelete(User $user, Curriculum $curriculum): bool
    {
        return $this->isHumanSuperAdmin($user);
    }

    private function canManage(User $user, Curriculum $curriculum): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        if ($curriculum->user_id !== null && (int) $curriculum->user_id === (int) $user->id) {
            return true;
        }

        return $this->adminCanManageOwner($user, $curriculum->user_id !== null ? (int) $curriculum->user_id : null);
    }
}
