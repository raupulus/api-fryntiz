<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Newsletter;
use App\Models\User;

class NewsletterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Newsletter $newsletter): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Newsletter $newsletter): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Newsletter $newsletter): bool
    {
        return $user->isSuperAdmin();
    }
}
