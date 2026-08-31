<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CV\Curriculum;
use App\Models\User;
use App\Support\Auth\TokenAbilities;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin() || $user->id === $curriculum->user_id;
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin() || $user->id === $curriculum->user_id;
    }

    public function delete(User $user, Curriculum $curriculum): bool
    {
        return $user->isAdmin() || $user->id === $curriculum->user_id;
    }
}
