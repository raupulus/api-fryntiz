<?php

namespace App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages;

use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\AirFlightAirPlaneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAirFlightAirPlanes extends ListRecords
{
    protected static string $resource = AirFlightAirPlaneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
