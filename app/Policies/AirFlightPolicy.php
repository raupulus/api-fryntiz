<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\User;

class AirFlightPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AirFlightAirPlane $airplane): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AirFlightAirPlane $airplane): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AirFlightAirPlane $airplane): bool
    {
        return $user->isSuperAdmin();
    }
}
