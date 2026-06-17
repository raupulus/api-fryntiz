<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\SmartPlantPlantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmartPlantPlant extends CreateRecord
{
    protected static string $resource = SmartPlantPlantResource::class;
}
