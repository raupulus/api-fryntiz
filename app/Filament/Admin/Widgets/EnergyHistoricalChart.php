<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Widget que muestra la generación vs consumo de energía de los últimos 30 días.
 */
class EnergyHistoricalChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Generación vs Consumo — 30 días';

    protected function getData(): array
    {
        $gen = HardwarePowerGeneratorHistorical::query()
            ->where('read_at', '>=', now()->subDays(30)->toDateString())
            ->orderBy('read_at')->get();
        $load = HardwarePowerLoadHistorical::query()
            ->where('read_at', '>=', now()->subDays(30)->toDateString())
            ->orderBy('read_at')->get();

        return [
            'datasets' => [
                ['label' => 'Generación (W)', 'data' => $gen->pluck('power'),
                    'borderColor' => 'rgb(34,197,94)', 'fill' => false],
                ['label' => 'Consumo (W)', 'data' => $load->pluck('power'),
                    'borderColor' => 'rgb(239,68,68)', 'fill' => false],
            ],
            'labels' => $gen->pluck('read_at')->map(fn ($d) => Carbon::parse($d)->format('d/m')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
