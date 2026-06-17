<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages;

use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\AirFlightRouteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAirFlightRoute extends EditRecord
{
    protected static string $resource = AirFlightRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
