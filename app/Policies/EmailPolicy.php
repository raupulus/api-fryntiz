<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Email;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy de Email.
 */
class EmailPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function view(User $user, Email $email): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function update(User $user, Email $email): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function delete(User $user, Email $email): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function restore(User $user, Email $email): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function forceDelete(User $user, Email $email): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
