<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareDevices extends ListRecords
{
    protected static string $resource = HardwareDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
