<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers;

use App\Models\Hardware\HardwareEnergy;

class BatteryRelationManager extends RoleRelationManager
{
    protected static function role(): string
    {
        return HardwareEnergy::ROLE_BATTERY;
    }

    protected static function explicacion(): string
    {
        return 'Lo que almacena: el banco de baterías. De esto hay uno por aparato, y es donde van los amperios-hora de carga.';
    }
}
