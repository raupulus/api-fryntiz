<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\EnergySystems\Pages;

use App\Filament\Admin\Resources\Hardware\EnergySystems\EnergySystemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEnergySystem extends CreateRecord
{
    protected static string $resource = EnergySystemResource::class;
}
