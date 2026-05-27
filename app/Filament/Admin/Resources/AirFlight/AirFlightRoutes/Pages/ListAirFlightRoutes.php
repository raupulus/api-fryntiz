<?php

namespace App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages;

use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\AirFlightRouteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAirFlightRoutes extends ListRecords
{
    protected static string $resource = AirFlightRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
