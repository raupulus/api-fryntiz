<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareTypes\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareTypes\HardwareTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareTypes extends ListRecords
{
    protected static string $resource = HardwareTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
