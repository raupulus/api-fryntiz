<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareEnergy extends EditRecord
{
    protected static string $resource = HardwareEnergyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
