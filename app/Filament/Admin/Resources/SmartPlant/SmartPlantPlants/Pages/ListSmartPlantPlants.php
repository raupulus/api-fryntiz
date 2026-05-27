<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\SmartPlantPlantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmartPlantPlants extends ListRecords
{
    protected static string $resource = SmartPlantPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
