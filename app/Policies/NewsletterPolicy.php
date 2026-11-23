<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Newsletter;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;

class NewsletterPolicy
{
    use GuardsAdminAndDeviceAccess;

    public function viewAny(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function view(User $user, Newsletter $newsletter): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function update(User $user, Newsletter $newsletter): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function delete(User $user, Newsletter $newsletter): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
