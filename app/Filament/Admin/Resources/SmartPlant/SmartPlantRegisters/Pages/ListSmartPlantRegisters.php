<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\SmartPlantRegisterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmartPlantRegisters extends ListRecords
{
    protected static string $resource = SmartPlantRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
