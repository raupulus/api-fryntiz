<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareDevice extends EditRecord
{
    protected static string $resource = HardwareDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
