<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHardwareEnergy extends CreateRecord
{
    protected static string $resource = HardwareEnergyResource::class;
}
