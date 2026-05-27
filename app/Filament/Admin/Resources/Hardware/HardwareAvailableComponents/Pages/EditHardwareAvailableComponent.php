<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\HardwareAvailableComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareAvailableComponent extends EditRecord
{
    protected static string $resource = HardwareAvailableComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
