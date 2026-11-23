<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre categorías del CMS.
 */
class CategoryPolicy
{
    use GuardsAdminAndDeviceAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isHumanAdmin($user) || $this->isHumanEditor($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
