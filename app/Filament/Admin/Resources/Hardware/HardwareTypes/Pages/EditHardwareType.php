<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareTypes\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareTypes\HardwareTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHardwareType extends EditRecord
{
    protected static string $resource = HardwareTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
