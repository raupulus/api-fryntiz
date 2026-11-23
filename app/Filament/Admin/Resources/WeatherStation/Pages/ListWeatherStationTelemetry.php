<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WeatherStation\Pages;

use App\Filament\Admin\Resources\WeatherStation\WeatherStationTelemetryResource;
use Filament\Resources\Pages\ListRecords;

class ListWeatherStationTelemetry extends ListRecords
{
    protected static string $resource = WeatherStationTelemetryResource::class;
}
