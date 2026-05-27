<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages;

use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\SmartPlantRegisterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmartPlantRegister extends EditRecord
{
    protected static string $resource = SmartPlantRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
