<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers;

use App\Models\Hardware\HardwareEnergy;

class GeneratorRelationManager extends RoleRelationManager
{
    protected static function role(): string
    {
        return HardwareEnergy::ROLE_GENERATOR;
    }

    protected static function explicacion(): string
    {
        return 'Lo que produce: el panel solar, el aerogenerador, el alternador. De esto hay uno por aparato.';
    }
}
