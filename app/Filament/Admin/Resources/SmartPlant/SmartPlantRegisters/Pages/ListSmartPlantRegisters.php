<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\SmartPlantRegisterResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Sin `CreateAction`: los registros los sube el dispositivo IoT, no se crean
 * a mano (ver `SmartPlantRegisterResource`).
 */
class ListSmartPlantRegisters extends ListRecords
{
    protected static string $resource = SmartPlantRegisterResource::class;
}
