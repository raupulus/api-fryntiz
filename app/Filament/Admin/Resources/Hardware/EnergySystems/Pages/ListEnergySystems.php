<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\EnergySystems\Pages;

use App\Filament\Admin\Resources\Hardware\EnergySystems\EnergySystemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnergySystems extends ListRecords
{
    protected static string $resource = EnergySystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
