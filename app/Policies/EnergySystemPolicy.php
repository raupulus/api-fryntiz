<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hardware\EnergySystem;
use Illuminate\Database\Eloquent\Model;

/**
 * Instalaciones energéticas (paneles, baterías, cargas).
 *
 * Borrar una arrastra su histórico de producción y consumo, que no se puede
 * reconstruir: es de las cosas del panel que más caro sale equivocar.
 */
class EnergySystemPolicy extends OwnedResourcePolicy
{
    protected function ownerId(Model $model): ?int
    {
        if (! $model instanceof EnergySystem) {
            return null;
        }

        return $model->user_id === null ? null : (int) $model->user_id;
    }
}
