<?php

namespace App\Filament\Admin\Widgets;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\Category;
use App\Models\Email;
use App\Models\Hardware\HardwareDevice;
use App\Models\Platform;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\Tag;
use App\Models\Technology;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Tecnologías', Technology::count())
                ->description('Total de tecnologías registradas')
                ->descriptionIcon('heroicon-m-code-bracket')
                ->color('danger'),

            Stat::make('Plataformas', Platform::count())
                ->description('Plataformas configuradas')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

            Stat::make('Etiquetas', Tag::count())
                ->description('Etiquetas para el contenido')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('Categorías', Category::count())
                ->description('Total de categorías registradas')
                ->descriptionIcon('heroicon-m-folder')
                ->color('warning'),

            Stat::make('Emails', Email::count())
                ->description('Total de emails registrados')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('success'),

            Stat::make('Aviones', AirFlightAirPlane::count())
                ->description('Aviones totales registrados')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('primary'),

            Stat::make('SmartPlants', SmartPlantPlant::count())
                ->description('Plantas inteligentes registradas')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),

            Stat::make('Dispositivos Hardware', HardwareDevice::count())
                ->description('Total de dispositivos hardware registrados')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('primary'),
        ];
    }
}
