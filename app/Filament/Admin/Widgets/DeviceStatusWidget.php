<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Hardware\HardwareDevice;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Widget de la página principal que muestra el último estado conocido de cada
 * dispositivo hardware que reporta al menos una métrica de estado (temperatura,
 * tensión, CPU o disco). Ocupa todo el ancho del dashboard.
 */
class DeviceStatusWidget extends Widget
{
    /**
     * Telemetría de servidores: nombres de nodos, CPU, disco, tensión y uptime
     * de todo el parque, sin filtrar por dueño. No es información para quien
     * entra al panel a escribir artículos (AR-SEC-04).
     */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected string $view = 'filament.admin.widgets.device-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    /**
     * Métricas de estado a mostrar por dispositivo con su icono, etiqueta,
     * color y unidad. Solo se renderizan las que no sean nulas.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getMetricsFor(HardwareDevice $device): array
    {
        $metrics = [
            ['key' => 'temp', 'label' => 'Temperatura', 'icon' => 'heroicon-o-fire', 'color' => 'orange', 'unit' => ' °C', 'decimals' => 1],
            ['key' => 'voltage', 'label' => 'Tensión', 'icon' => 'heroicon-o-bolt', 'color' => 'amber', 'unit' => ' V', 'decimals' => 2],
            ['key' => 'cpu', 'label' => 'CPU', 'icon' => 'heroicon-o-cpu-chip', 'color' => 'sky', 'unit' => ' %', 'decimals' => 0],
            ['key' => 'disk', 'label' => 'Disco', 'icon' => 'heroicon-o-circle-stack', 'color' => 'indigo', 'unit' => ' %', 'decimals' => 0],
            ['key' => 'ram', 'label' => 'RAM', 'icon' => 'heroicon-o-square-3-stack-3d', 'color' => 'violet', 'unit' => ' %', 'decimals' => 0],
            ['key' => 'uptime', 'label' => 'Uptime', 'icon' => 'heroicon-o-clock', 'color' => 'emerald', 'unit' => '', 'decimals' => 0],
            ['key' => 'battery_level', 'label' => 'Batería', 'icon' => 'heroicon-o-battery-50', 'color' => 'green', 'unit' => ' %', 'decimals' => 0],
        ];

        $result = [];

        foreach ($metrics as $metric) {
            $value = $device->{$metric['key']};

            if ($value === null) {
                continue;
            }

            if ($metric['key'] === 'uptime') {
                $formatted = $this->formatUptime((int) $value);
            } else {
                $formatted = number_format((float) $value, $metric['decimals']).$metric['unit'];
            }

            $metric['value'] = $formatted;
            $result[] = $metric;
        }

        return $result;
    }

    /**
     * Formatea segundos de actividad a un texto legible (d/h/m).
     */
    protected function formatUptime(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.'d';
        }

        if ($hours > 0) {
            $parts[] = $hours.'h';
        }

        if ($minutes > 0 || $parts === []) {
            $parts[] = $minutes.'m';
        }

        return implode(' ', $parts);
    }

    protected function getViewData(): array
    {
        /** @var Collection<int, array<string, mixed>> $devices */
        $devices = HardwareDevice::query()
            ->where(function ($query) {
                $query->whereNotNull('temp')
                    ->orWhereNotNull('voltage')
                    ->orWhereNotNull('cpu')
                    ->orWhereNotNull('disk')
                    ->orWhereNotNull('ram');
            })
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (HardwareDevice $device) => [
                'name' => $device->display_name,
                'last_seen_at' => $device->last_seen_at,
                'metrics' => $this->getMetricsFor($device),
            ]);

        return [
            'devices' => $devices,
        ];
    }
}
