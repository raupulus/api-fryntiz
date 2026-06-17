<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Content\Content;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Content $content): bool
    {
        return $user->isAdmin() || $user->id === $content->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Content $content): bool
    {
        return $user->isAdmin() || $user->id === $content->user_id;
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->isAdmin();
    }
}
