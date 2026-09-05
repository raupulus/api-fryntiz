<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AirFlight\AirFlightRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Rutas de vuelo guardadas.
 *
 * A diferencia de las aeronaves ({@see AirFlightPolicy}), que son un catálogo
 * público de lectura, una ruta la guarda un usuario concreto.
 */
class AirFlightRoutePolicy extends OwnedResourcePolicy
{
    /**
     * Las rutas son datos de vuelo públicos: se leen en abierto, como las
     * aeronaves. Lo que no es público es modificarlas.
     */
    public function view(User $user, Model $model): bool
    {
        return true;
    }

    protected function ownerId(Model $model): ?int
    {
        if (! $model instanceof AirFlightRoute) {
            return null;
        }

        return $model->user_id === null ? null : (int) $model->user_id;
    }
}
