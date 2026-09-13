<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\Pages;

use App\Filament\Admin\Resources\Energy\EnergyDevices\EnergyDeviceResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Sin botón de crear: los aparatos se dan de alta en Hardware, aquí sólo se
 * gestiona su cara energética.
 */
class ListEnergyDevices extends ListRecords
{
    protected static string $resource = EnergyDeviceResource::class;
}
