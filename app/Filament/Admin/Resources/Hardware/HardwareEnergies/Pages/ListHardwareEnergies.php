<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHardwareEnergies extends ListRecords
{
    protected static string $resource = HardwareEnergyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
