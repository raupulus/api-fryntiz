<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

use function asset;
use function date;
use function number_format;
use function round;

/**
 * Class EnergyController
 *
 * Gestiona peticiones para el consumo y la producción de energía en la web pública.
 */
class EnergyController extends Controller
{
    public function index(): View
    {
        $dateToday = date('Y-m-d');
        $lastHour = Carbon::now()->subHour();

        // Dispositivos que tienen elementos energéticos configurados o telemetría
        $hardwareItems = HardwareDevice::with('image.fileType')
            ->where(function (Builder $query) {
                $query->whereHas('hardwareEnergy')
                    ->orWhereHas('energyHistorical', static fn (Builder $q) => $q->whereNotNull('energy_wh'))
                    ->orWhereHas('energyToday', static fn (Builder $q) => $q->whereNotNull('energy_wh'))
                    ->orWhereHas('energyReadings', static fn (Builder $q) => $q->whereNotNull('power'));
            })
            ->get();

        $hardwareIds = $hardwareItems->pluck('id')->toArray();

        // Lecturas más recientes de la última hora por canal/rol de energía
        $generatorCurrent = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('created_at', '>=', $lastHour)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR)->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $hardwareIds)
                ->where('created_at', '>=', $lastHour)
                ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR)->where('is_active', true))
                ->groupBy('hardware_energy_id'))
            ->get();

        $loadCurrent = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('created_at', '>=', $lastHour)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD)->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $hardwareIds)
                ->where('created_at', '>=', $lastHour)
                ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD)->where('is_active', true))
                ->groupBy('hardware_energy_id'))
            ->get();

        // Agregados de hoy
        $generatorToday = HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('date', $dateToday)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR))
            ->get();

        $loadToday = HardwareEnergyToday::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('date', $dateToday)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD))
            ->get();

        // Acumulados históricos
        $generatorHistorical = HardwareEnergyHistorical::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_GENERATOR))
            ->get();

        $loadHistorical = HardwareEnergyHistorical::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_LOAD))
            ->get();

        // Objeto con los cálculos agregados de producción (generador)
        $generator = (object) [
            'current' => round((float) $generatorCurrent->sum('power')),
            'current_amperage' => round((float) $generatorCurrent->sum('amperage')),
            'current_voltage' => number_format((float) ($generatorCurrent->avg('voltage') ?? 0), 1),
            'today' => round((float) $generatorToday->sum('energy_wh')),
            'today_amperage' => round((float) $generatorToday->sum('energy_ah')),
            'historical' => number_format((float) $generatorHistorical->sum('energy_wh') / 1000, 1),
            'days_operating' => (int) ($generatorHistorical->sum('days_operating')),
            'battery_full_charge' => number_format((float) $generatorHistorical->sum('number_battery_full_charges')),
            'battery_percentage' => number_format((float) ($generatorCurrent->whereNotNull('battery_percentage')->avg('battery_percentage') ?? 0)),
            'max_light' => number_format((float) ($generatorCurrent->max('light_brightness') ?? 0)),
            'max_temp' => number_format((float) ($generatorCurrent->max('temperature') ?? 0), 1),
        ];

        // Objeto con los cálculos agregados de consumo (carga)
        $load = (object) [
            'current' => round((float) $loadCurrent->sum('power')),
            'current_amperage' => number_format((float) $loadCurrent->sum('amperage'), 1),
            'current_voltage' => number_format((float) ($loadCurrent->avg('voltage') ?? 0), 1),
            'today' => round((float) $loadToday->sum('energy_wh')),
            'today_amperage' => round((float) $loadToday->sum('energy_ah')),
            'historical' => number_format((float) $loadHistorical->sum('energy_wh') / 1000, 1),
            'battery_percentage' => number_format((float) ($loadCurrent->whereNotNull('battery_percentage')->avg('battery_percentage') ?? 0)),
            'max_temp' => number_format((float) ($loadCurrent->max('temperature') ?? 0), 1),
        ];

        // Estadísticas Históricas para la sección correspondiente
        $historicalStats = [
            [
                'title' => 'Generado',
                'value' => $generator->historical,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'kWh',
            ], [
                'title' => 'Consumido',
                'value' => $load->historical,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'kWh',
            ], [
                'title' => 'Días Operando',
                'value' => $generator->days_operating,
                'image' => asset('images/icons/binary-code-numbers-on-monitor-screen.svg'),
                'unit' => 'd',
            ], [
                'title' => 'Cargas',
                'value' => $generator->battery_full_charge,
                'image' => asset('images/icons/battery-status.svg'),
                'unit' => '',
            ],
        ];

        // Estadísticas de Hoy
        $todayStats = [
            [
                'title' => 'Generado',
                'value' => $generator->today,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'Wh',
            ], [
                'title' => 'Consumido',
                'value' => $load->today,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'Wh',
            ], [
                'title' => 'Generado (panel)',
                'value' => $generator->today_amperage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'Ah',
            ], [
                'title' => 'Consumido (batería)',
                'value' => $load->today_amperage,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'Ah',
            ],
        ];

        // Estadísticas en tiempo real (Ahora mismo)
        $currentStats = [
            [
                'title' => 'Generando',
                'value' => $generator->current,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'W',
            ], [
                'title' => 'Consumiendo',
                'value' => $load->current,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'W',
            ], [
                'title' => 'Balance',
                'value' => round($generator->current - $load->current),
                'image' => asset('images/icons/battery-status.svg'),
                'unit' => 'W',
            ], [
                'title' => 'Panel / Batería',
                'value' => $generator->current_voltage.' / '.$load->current_voltage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'V',
            ], [
                'title' => 'Bat. Charge',
                'value' => $generator->battery_percentage ?? 0,
                'image' => asset('images/icons/battery-status.svg'),
                'unit' => '%',
                'list' => '',
            ], [
                'title' => 'Bat. Load',
                'value' => $load->battery_percentage ?? 0,
                'image' => asset('images/icons/battery-status.svg'),
                'unit' => '%',
            ], [
                'title' => 'Luz Calle',
                'value' => $generator->max_light,
                'image' => asset('images/icons/day-moon.svg'),
                'unit' => '%',
            ], [
                'title' => 'Max. Temp',
                'value' => ($load->max_temp > $generator->max_temp) ? $load->max_temp : $generator->max_temp,
                'image' => asset('images/icons/temperature.svg'),
                'unit' => 'ºC',
            ],
        ];

        // Estadísticas individuales por cada dispositivo para sus tarjetas
        $devicesStats = $hardwareItems->mapWithKeys(function (HardwareDevice $hw) use (
            $generatorCurrent, $generatorToday, $generatorHistorical,
            $loadCurrent, $loadToday, $loadHistorical
        ) {
            $genCurrent = $generatorCurrent->where('hardware_device_id', $hw->id);
            $genToday = $generatorToday->where('hardware_device_id', $hw->id);
            $genHistorical = $generatorHistorical->where('hardware_device_id', $hw->id);

            $loadCurrentDev = $loadCurrent->where('hardware_device_id', $hw->id);
            $loadTodayDev = $loadToday->where('hardware_device_id', $hw->id);
            $loadHistoricalDev = $loadHistorical->where('hardware_device_id', $hw->id);

            return [$hw->id => (object) [
                'generated_now' => (float) $genCurrent->sum('power'),
                'generated_today' => (float) $genToday->sum('energy_wh'),
                'consumed_now' => (float) $loadCurrentDev->sum('power'),
                'consumed_today' => (float) $loadTodayDev->sum('energy_wh'),
                'battery_percentage' => (int) round((float) ($genCurrent->whereNotNull('battery_percentage')->avg('battery_percentage') ?? 0)),
                'days_operating' => (int) $genHistorical->sum('days_operating'),
                'generated_historical_kwh' => round((float) $genHistorical->sum('energy_wh') / 1000, 2),
                'consumed_historical_kwh' => round((float) $loadHistoricalDev->sum('energy_wh') / 1000, 2),
            ]];
        });

        // Ordenación en cascada: (1) Activos en la última hora > (2) Energía movida hoy > (3) Acumulado de siempre
        $hardwareItems = $hardwareItems->sortByDesc(function (HardwareDevice $hw) use ($devicesStats) {
            $stats = $devicesStats[$hw->id];

            return [
                ($stats->generated_now > 0 || $stats->consumed_now > 0) ? 1 : 0,
                $stats->generated_today + $stats->consumed_today,
                $stats->generated_historical_kwh + $stats->consumed_historical_kwh,
            ];
        })->values();

        return view('hardware.energy.index', [
            'generator' => $generator,
            'load' => $load,
            'hardwareItems' => $hardwareItems,
            'devicesStats' => $devicesStats,
            'historicalStats' => $historicalStats,
            'todayStats' => $todayStats,
            'currentStats' => $currentStats,
        ]);
    }
}
