<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\AemetDashboard;
use App\Models\WeatherStation\AEMET\AEMETContamination;
use App\Models\WeatherStation\AEMET\AEMETPrediction;
use Filament\Widgets\Widget;

class AemetMetricsWidget extends Widget
{
    /**
     * Coherencia con {@see AemetDashboard}, que ya
     * exige administrador: el widget mostraba en el escritorio lo mismo que la
     * página tenía cerrado (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected string $view = 'filament.admin.widgets.aemet-metrics-widget';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $latestPrediction = AEMETPrediction::latest('start_at')->first();
        $latestContamination = AEMETContamination::latest('read_at')->first();

        // Convert wind direction degrees to cardinal points
        $degrees = $latestContamination?->wind_direction;
        $cardinal = 'N/D';
        if ($degrees !== null) {
            $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N'];
            $cardinal = $directions[round($degrees / 22.5) % 16];
        }

        return [
            'temperature' => $latestPrediction?->temperature ?? $latestContamination?->temperature ?? 0,
            'wind_max' => $latestContamination?->wind_speed ?? 0,
            'wind_direction' => $cardinal.($degrees !== null ? " ({$degrees}°)" : ''),
            'solar_radiation' => $latestContamination?->radiation_global ?? 0,
            'humidity' => $latestPrediction?->humidity ?? $latestContamination?->humidity ?? 0,
            'rain_prob' => $latestPrediction?->rain_prob ?? 0,
            'ozone' => $latestContamination?->o3 ?? 0,
            'pollen' => 'N/D',
        ];
    }
}
