<?php

namespace App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages;

use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\AirFlightAirPlaneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAirFlightAirPlane extends EditRecord
{
    protected static string $resource = AirFlightAirPlaneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
