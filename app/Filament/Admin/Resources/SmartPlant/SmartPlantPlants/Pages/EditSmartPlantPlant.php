<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\SmartPlantPlantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmartPlantPlant extends EditRecord
{
    protected static string $resource = SmartPlantPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
