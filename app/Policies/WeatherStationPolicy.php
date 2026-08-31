<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización sobre las lecturas de la estación meteorológica.
 *
 * Los datos se publican en abierto (los consumen las webs sin autenticarse),
 * así que la lectura no se restringe. Lo que sí se restringe es tocarlos a
 * mano: eso es administración, y en ningún caso lo hace un token de
 * dispositivo, que escribe por su endpoint y nada más.
 */
class WeatherStationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $record): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    public function delete(User $user, ?Model $record = null): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->delete($user, $record);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $this->delete($user, $record);
    }
}
