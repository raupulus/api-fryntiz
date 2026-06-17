<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\Widgets;

use App\Models\SmartPlant\SmartPlantRegister;
use Filament\Widgets\ChartWidget;

/**
 * Widget que muestra la humedad del suelo de una planta en las últimas 24h.
 */
class SoilHumidityChart extends ChartWidget
{
    protected ?string $heading = 'Humedad del suelo — últimas 24h';

    protected static ?int $sort = 1;

    public ?int $plantId = null;

    protected function getData(): array
    {
        $data = SmartPlantRegister::query()
            ->where('plant_id', $this->plantId)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->get(['created_at', 'soil_humidity']);

        return [
            'datasets' => [[
                'label' => 'Humedad suelo (%)',
                'data' => $data->pluck('soil_humidity'),
                'borderColor' => 'rgb(34, 197, 94)',
                'fill' => false,
            ]],
            'labels' => $data->pluck('created_at')->map(fn ($d) => $d->format('H:i')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
