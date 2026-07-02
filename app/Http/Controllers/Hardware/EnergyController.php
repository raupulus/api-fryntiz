<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
use App\Models\Hardware\HardwarePowerLoad;
use App\Models\Hardware\HardwarePowerLoadHistorical;
use App\Models\Hardware\HardwarePowerLoadToday;
use Carbon\Carbon;

use function asset;
use function number_format;
use function round;

/**
 * Class EnergyController
 *
 * Gestiona peticiones para el consumo y la producción de energía.
 */
class EnergyController extends Controller
{
    public function index()
    {
        $dateToday = date('Y-m-d');

        $hardwares = HardwareDevice::with('image', 'powerLoadsHistorical', 'powerGeneratorsHistorical')
            ->where(function ($query) {
                $query->whereHas('powerLoadsHistorical', function ($q) {
                    $q->whereNotNull('power');
                })->orWhereHas('powerGeneratorsHistorical', function ($q) {
                    $q->whereNotNull('power');
                });
            })
            ->get();
        $hardware_ids = $hardwares->pluck('id')->toArray();

        $lastHour = Carbon::now()->subHour();

        // # Obtengo el último registro por cada dispositivo en la última hora.
        $hardwareGeneratorCurrentIds = HardwarePowerGenerator::whereIn('hardware_device_id', $hardware_ids)
            ->where('read_at', '>=', $lastHour)
            ->whereRaw('id in (select max(id) from hardware_power_generators group by hardware_device_id)')
            ->pluck('id')
            ->toArray();
        $hardwareGeneratorCurrent = HardwarePowerGenerator::whereIn('id', $hardwareGeneratorCurrentIds)->get();

        // # Registros de dispositivos por día.
        $hardwareGeneratorToday = HardwarePowerGeneratorToday::whereIn('hardware_device_id', $hardware_ids)
            ->where('date', $dateToday)
            ->get();

        // # Registros de dispositivos por siempre.
        $hardwareGeneratorHistorical = HardwarePowerGeneratorHistorical::whereIn('hardware_device_id', $hardware_ids)->get();

        // # Registro de energía consumida por dispositivo en la última hora.
        $hardwareLoadCurrentIds = HardwarePowerLoad::whereIn('hardware_device_id', $hardware_ids)
            ->where('read_at', '>=', $lastHour)
            ->whereRaw('id in (select max(id) from hardware_power_loads group by hardware_device_id)')
            ->pluck('id')
            ->toArray();

        $hardwareLoadCurrent = HardwarePowerLoad::whereIn('id', $hardwareLoadCurrentIds)->get();

        // # Registros de carga de energía por día.
        $hardwareLoadToday = HardwarePowerLoadToday::whereIn('hardware_device_id', $hardware_ids)
            ->where('date', $dateToday)
            ->get();

        // # Registros de carga de energía por siempre.
        $hardwareLoadHistorical = HardwarePowerLoadHistorical::whereIn('hardware_device_id', $hardware_ids)->get();

        // # Objeto con los cálculos de producción.
        $generator = (object) [
            'current' => round($hardwareGeneratorCurrent->sum('power')),
            'current_amperage' => round($hardwareGeneratorCurrent->sum('amperage')),
            'today' => round($hardwareGeneratorToday->sum('power')),
            'today_amperage' => round($hardwareGeneratorToday->sum('amperage')),
            'historical' => number_format($hardwareGeneratorHistorical->sum('power') / 1000, 1),
            'days_operating' => $hardwareGeneratorHistorical->sum('days_operating'),
            'battery_full_charge' => number_format($hardwareGeneratorHistorical->sum('number_battery_full_charges')),
            'battery_percentage' => number_format((float) ($hardwareGeneratorCurrent->avg('battery_percentage') ?? 0)),
            'max_light' => number_format((float) ($hardwareGeneratorCurrent->max('light_brightness') ?? 0)),
            'max_temp' => number_format((float) ($hardwareGeneratorCurrent->max('battery_temperature') ?? 0), 1),
        ];

        // # Objeto con los cálculos de consumo.
        $load = (object) [
            'current' => round($hardwareLoadCurrent->sum('power')),
            'current_amperage' => number_format($hardwareLoadCurrent->sum('amperage'), 1),
            'today' => round($hardwareLoadToday->sum('power')),
            'today_amperage' => round($hardwareLoadToday->sum('amperage')),
            'historical' => number_format($hardwareLoadHistorical->sum('power') / 1000, 1),
            'battery_percentage' => number_format((float) ($hardwareLoadCurrent->avg('battery_percentage') ?? 0)),
            'max_temp' => number_format((float) ($hardwareLoadCurrent->max('temperature') ?? 0), 1),
        ];

        // # Estadísticas Históricas.
        $historicalStats = [
            [
                'title' => 'Generado',
                'value' => $generator->historical,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'kw',
            ], [
                'title' => 'Consumido',
                'value' => $load->historical,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'kw',
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

        $todayStats = [
            [
                'title' => 'Generado',
                'value' => $generator->today,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'W',
            ], [
                'title' => 'Consumido',
                'value' => $load->today,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'W',
            ], [
                'title' => 'Generado',
                'value' => $generator->today_amperage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'ah',
            ], [
                'title' => 'Consumido',
                'value' => $load->today_amperage,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'ah',
            ],
        ];

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
                'title' => 'Generando',
                'value' => $generator->current_amperage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'ah',
            ], [
                'title' => 'Consumiendo',
                'value' => $load->current_amperage,
                'image' => asset('images/icons/energy-green.svg'),
                'unit' => 'ah',
            ],

            [
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

        // # Estadísticas individuales por cada dispositivo, usadas en sus tarjetas.
        $devicesStats = $hardwares->mapWithKeys(function ($hw) use (
            $hardwareGeneratorCurrent, $hardwareGeneratorToday, $hardwareGeneratorHistorical,
            $hardwareLoadCurrent, $hardwareLoadToday, $hardwareLoadHistorical
        ) {
            $genCurrent = $hardwareGeneratorCurrent->firstWhere('hardware_device_id', $hw->id);
            $genToday = $hardwareGeneratorToday->firstWhere('hardware_device_id', $hw->id);
            $genHistorical = $hardwareGeneratorHistorical->where('hardware_device_id', $hw->id);

            $loadCurrentHw = $hardwareLoadCurrent->firstWhere('hardware_device_id', $hw->id);
            $loadTodayHw = $hardwareLoadToday->firstWhere('hardware_device_id', $hw->id);
            $loadHistoricalHw = $hardwareLoadHistorical->where('hardware_device_id', $hw->id);

            return [$hw->id => (object) [
                'generated_now' => (float) ($genCurrent->power ?? 0),
                'generated_today' => (float) ($genToday->power ?? 0),
                'consumed_now' => (float) ($loadCurrentHw->power ?? 0),
                'consumed_today' => (float) ($loadTodayHw->power ?? 0),
                'battery_percentage' => (int) round((float) ($genCurrent->battery_percentage ?? 0)),
                'days_operating' => (int) $genHistorical->sum('days_operating'),
                'generated_historical_kw' => round($genHistorical->sum('power') / 1000, 2),
                'consumed_historical_kw' => round($loadHistoricalHw->sum('power') / 1000, 2),
            ]];
        });

        return view('hardware.energy.index', [
            'generator' => $generator,
            'load' => $load,
            'hardwares' => $hardwares,
            'devicesStats' => $devicesStats,
            'historicalStats' => $historicalStats,
            'todayStats' => $todayStats,
            'currentStats' => $currentStats,
        ]);
    }
}
