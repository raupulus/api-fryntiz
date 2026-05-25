<?php

namespace App\Policies;

use App\Models\CV\Curriculum;
use App\Models\User;

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
        return true;
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
