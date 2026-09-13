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
use Illuminate\Support\Collection;
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
    /**
     * Tensión de referencia cuando la instalación no declara ninguna.
     *
     * 12 V es la del bus de la instalación real: el Renogy carga a 12 V y
     * alimenta los consumos a 12 V aunque el panel genere a 24 V.
     */
    private const FALLBACK_REFERENCE_VOLTAGE = 12.0;

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

        $batteryCurrent = HardwareEnergyReading::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('created_at', '>=', $lastHour)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_BATTERY)->where('is_active', true))
            ->whereIn('id', HardwareEnergyReading::query()
                ->selectRaw('MAX(id)')
                ->whereIn('hardware_device_id', $hardwareIds)
                ->where('created_at', '>=', $lastHour)
                ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_BATTERY)->where('is_active', true))
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

        $batteryHistorical = HardwareEnergyHistorical::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->whereHas('hardwareEnergy', static fn (Builder $q) => $q->where('role', HardwareEnergy::ROLE_BATTERY))
            ->get();

        $batteryPercentageAvg = $batteryCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? $generatorCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? $loadCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? 0;

        $batteryFullChargesSum = (float) (
            $batteryHistorical->sum('number_battery_full_charges') > 0
                ? $batteryHistorical->sum('number_battery_full_charges')
                : $generatorHistorical->sum('number_battery_full_charges')
        );

        // **La tensión de referencia de la instalación.**
        //
        // Comparar amperios medidos a tensiones distintas no significa nada: el
        // panel del Renogy genera a 24 V y el consumo va a 12 V, así que generar
        // 5 A no compensa consumir 5 A —son 10 A referidos a 12 V frente a 5—.
        // Todo lo que se pinte en amperios se lleva antes a esta tensión.
        $referenceVoltage = $this->referenceVoltage($hardwareIds);

        // Objeto con los cálculos agregados de producción (generador)
        $generator = (object) [
            'current' => round((float) $generatorCurrent->sum('power')),
            'current_amperage' => round($this->amperageAtReference($generatorCurrent, $referenceVoltage), 1),
            'current_voltage' => number_format((float) ($generatorCurrent->avg('voltage') ?? 0), 1),
            'today' => round((float) $generatorToday->sum('energy_wh')),
            'today_amperage' => round((float) $generatorToday->sum('energy_wh') / $referenceVoltage),
            'historical' => number_format((float) $generatorHistorical->sum('energy_wh') / 1000, 1),
            'days_operating' => (int) ($generatorHistorical->max('days_operating') ?? 0),
            'battery_full_charge' => number_format($batteryFullChargesSum),
            'battery_percentage' => number_format((float) $batteryPercentageAvg),
            'max_light' => number_format((float) ($generatorCurrent->max('light_brightness') ?? 0)),
            'max_temp' => number_format((float) ($generatorCurrent->max('temperature') ?? 0), 1),
        ];

        // Objeto con los cálculos agregados de consumo (carga)
        $load = (object) [
            'current' => round((float) $loadCurrent->sum('power')),
            'current_amperage' => number_format($this->amperageAtReference($loadCurrent, $referenceVoltage), 1),
            'current_voltage' => number_format((float) ($loadCurrent->avg('voltage') ?? 0), 1),
            'today' => round((float) $loadToday->sum('energy_wh')),
            'today_amperage' => round((float) $loadToday->sum('energy_wh') / $referenceVoltage),
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
                // Los dos van referidos a la misma tensión, así que aquí sí se
                // pueden comparar y restar. Sin eso, «65 Ah generados» a 24 V y
                // «36 Ah consumidos» a 12 V invitan a una resta que sale mal.
                'title' => 'Generado a '.$referenceVoltage.' V',
                'value' => $generator->today_amperage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'Ah',
            ], [
                'title' => 'Consumido a '.$referenceVoltage.' V',
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
                // En vatios el balance ya sale bien porque la potencia no
                // depende de la tensión; en amperios hay que referirlos antes.
                'title' => 'Balance a '.$referenceVoltage.' V',
                'value' => round(($generator->current - $load->current) / $referenceVoltage, 1),
                'image' => asset('images/icons/battery-status.svg'),
                'unit' => 'A',
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
            $loadCurrent, $loadToday, $loadHistorical, $batteryCurrent
        ) {
            $genCurrent = $generatorCurrent->where('hardware_device_id', $hw->id);
            $genToday = $generatorToday->where('hardware_device_id', $hw->id);
            $genHistorical = $generatorHistorical->where('hardware_device_id', $hw->id);

            $loadCurrentDev = $loadCurrent->where('hardware_device_id', $hw->id);
            $loadTodayDev = $loadToday->where('hardware_device_id', $hw->id);
            $loadHistoricalDev = $loadHistorical->where('hardware_device_id', $hw->id);

            $batCurrentDev = $batteryCurrent->where('hardware_device_id', $hw->id);
            $batPercentage = $batCurrentDev->whereNotNull('battery_percentage')->avg('battery_percentage')
                ?? $genCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
                ?? $loadCurrentDev->whereNotNull('battery_percentage')->avg('battery_percentage')
                ?? 0;

            return [$hw->id => (object) [
                'generated_now' => (float) $genCurrent->sum('power'),
                'generated_today' => (float) $genToday->sum('energy_wh'),
                'consumed_now' => (float) $loadCurrentDev->sum('power'),
                'consumed_today' => (float) $loadTodayDev->sum('energy_wh'),
                'battery_percentage' => (int) round((float) $batPercentage),
                'days_operating' => (int) ($genHistorical->max('days_operating') ?? 0),
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

    /**
     * La tensión a la que se refieren todos los amperios que se pintan.
     *
     * Sin una referencia común, poner «generado 65 Ah» al lado de «consumido
     * 36 Ah» induce a restar dos números que no son comparables: los primeros
     * se midieron a 24 V en el panel y los segundos a 12 V en la salida de
     * carga. Un amperio a 24 V mueve el doble de energía que uno a 12 V.
     *
     * Se toma la nominal de la batería, que es la tensión del bus de la
     * instalación —lo que el Renogy Rover llama tensión de sistema—. Si no hay
     * batería configurada se cae a la del consumo, y si tampoco, a 12 V, que es
     * lo que hay montado.
     *
     * @param  list<int>  $hardwareIds
     */
    private function referenceVoltage(array $hardwareIds): float
    {
        $porRol = static fn (string $role): ?float => HardwareEnergy::query()
            ->whereIn('hardware_device_id', $hardwareIds)
            ->where('role', $role)
            ->where('is_active', true)
            ->whereNotNull('nominal_voltage')
            ->orderBy('id')
            ->value('nominal_voltage');

        $voltage = $porRol(HardwareEnergy::ROLE_BATTERY)
            ?? $porRol(HardwareEnergy::ROLE_LOAD)
            ?? self::FALLBACK_REFERENCE_VOLTAGE;

        return (float) $voltage > 0.0 ? (float) $voltage : self::FALLBACK_REFERENCE_VOLTAGE;
    }

    /**
     * Los amperios de un conjunto de lecturas, referidos a una tensión común.
     *
     * Se pasa por la potencia, que no depende de la tensión: `A_ref = W / V_ref`.
     * Así una lectura de 5 A a 24 V cuenta como 10 A a 12 V, que es lo que de
     * verdad aporta al balance.
     *
     * @param  Collection<int, HardwareEnergyReading>  $readings
     */
    private function amperageAtReference($readings, float $referenceVoltage): float
    {
        if ($referenceVoltage <= 0.0) {
            return 0.0;
        }

        return (float) $readings->sum('power') / $referenceVoltage;
    }
}
