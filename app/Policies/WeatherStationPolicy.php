<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class WeatherStationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
