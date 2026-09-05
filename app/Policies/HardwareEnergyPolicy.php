<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hardware\HardwareEnergy;
use Illuminate\Database\Eloquent\Model;

/**
 * Módulos de energía de un dispositivo.
 *
 * La tabla `hardware_energy` no tiene `user_id`: la propiedad vive en el
 * sistema energético del que cuelga el módulo, igual que hace su propio scope
 * `forUser()` ({@see HardwareEnergy::scopeForUser()}).
 */
class HardwareEnergyPolicy extends OwnedResourcePolicy
{
    protected function ownerId(Model $model): ?int
    {
        if (! $model instanceof HardwareEnergy) {
            return null;
        }

        $userId = $model->system?->user_id;

        return $userId === null ? null : (int) $userId;
    }
}
