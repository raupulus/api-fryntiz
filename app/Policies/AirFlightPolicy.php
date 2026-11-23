<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\User;
use App\Policies\Concerns\GuardsAdminAndDeviceAccess;

class AirFlightPolicy
{
    use GuardsAdminAndDeviceAccess;

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
        return $this->isHumanAdmin($user);
    }

    public function update(User $user, AirFlightAirPlane $airplane): bool
    {
        return $this->isHumanAdmin($user);
    }

    public function delete(User $user, AirFlightAirPlane $airplane): bool
    {
        return $this->isHumanSuperAdmin($user);
    }
}
