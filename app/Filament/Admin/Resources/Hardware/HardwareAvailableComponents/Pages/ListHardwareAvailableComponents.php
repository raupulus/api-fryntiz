<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareAvailableComponents\HardwareAvailableComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareAvailableComponents extends ListRecords
{
    protected static string $resource = HardwareAvailableComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
