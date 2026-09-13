<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers;

use App\Models\Hardware\HardwareEnergy;

class LoadRelationManager extends RoleRelationManager
{
    protected static function role(): string
    {
        return HardwareEnergy::ROLE_LOAD;
    }

    protected static function explicacion(): string
    {
        return 'Lo que gasta: la salida de carga, un router, una Raspberry. De esto hay tantos como canales mida el aparato, y cada uno en el suyo.';
    }
}
