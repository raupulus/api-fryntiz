<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Email;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy de Email.
 *
 * Reescrito en fix_10 — los argumentos previos usaban Platform en lugar de Email.
 */
class EmailPolicy
{
    use HandlesAuthorization;

    protected function isAdmin(User $user): bool
    {
        return $user->role && in_array($user->role->slug, ['admin', 'superadmin'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Email $email): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Email $email): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Email $email): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Email $email): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Email $email): bool
    {
        return $this->isAdmin($user);
    }
}
