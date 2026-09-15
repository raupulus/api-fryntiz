<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\Hardware\HardwareType;
use App\Support\Format\Figures;
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
        $hardwareItems = HardwareDevice::with([
            'image.fileType',
            // Para separar la instalación solar del resto de cacharros. Sin el
            // eager load sería una consulta por fila, y con el lazy loading
            // desactivado, una excepción.
            'type',
            // Para la tarjeta: saber si tiene generador, y el nombre/canal de
            // cada consumo. Sin este eager load sería una consulta por fila
            // (el proyecto tiene el lazy loading desactivado, así que además
            // reventaría en vez de ir despacio en silencio).
            'hardwareEnergy' => fn ($q) => $q->where('is_active', true)->orderBy('sensor_position'),
            'hardwareEnergy.monitorized',
        ])
            ->where(function (Builder $query) {
                $query->whereHas('hardwareEnergy')
                    ->orWhereHas('energyHistorical', static fn (Builder $q) => $q->whereNotNull('energy_wh'))
                    ->orWhereHas('energyToday', static fn (Builder $q) => $q->whereNotNull('energy_wh'))
                    ->orWhereHas('energyReadings', static fn (Builder $q) => $q->whereNotNull('power'));
            })
            ->get();

        $hardwareIds = $hardwareItems->pluck('id')->toArray();

        // **Todo lo que va por encima de «Dispositivos» es la instalación
        // solar.**
        //
        // Las tarjetas de abajo son de cada aparato y ahí sí entra todo, pero
        // los totales de arriba describen el sistema fotovoltaico: sumarles un
        // consumo enchufado a la red de casa no sólo infla el total, es que lo
        // convierte en otra cosa. La Raspberry Pi 5 mide su propio consumo y el
        // de su Hailo-8 a 5 V y 3,3 V de la red; promediar su tensión con la del
        // Rover daba una tarjeta «Panel / Bat. / Consumo» con 7 V de consumo,
        // que no es la tensión de ningún sitio —(12,5 + 5,1 + 3,3) / 3—.
        $solarIds = $hardwareItems
            ->filter(static fn (HardwareDevice $hw): bool => $hw->type?->slug === HardwareType::SOLAR_CONTROLLER_SLUG)
            ->pluck('id')
            ->all();

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

        // Las consultas de arriba traen todos los dispositivos porque las
        // tarjetas de «Dispositivos» los necesitan enteros. Los agregados de
        // cabecera trabajan sobre estas, que son sólo las de la instalación
        // solar. Se filtra en memoria: los datos ya están traídos y así no hay
        // una segunda tanda de consultas.
        $solarGeneratorCurrent = $generatorCurrent->whereIn('hardware_device_id', $solarIds);
        $solarLoadCurrent = $loadCurrent->whereIn('hardware_device_id', $solarIds);
        $solarBatteryCurrent = $batteryCurrent->whereIn('hardware_device_id', $solarIds);
        $solarGeneratorToday = $generatorToday->whereIn('hardware_device_id', $solarIds);
        $solarLoadToday = $loadToday->whereIn('hardware_device_id', $solarIds);
        $solarGeneratorHistorical = $generatorHistorical->whereIn('hardware_device_id', $solarIds);
        $solarLoadHistorical = $loadHistorical->whereIn('hardware_device_id', $solarIds);
        $solarBatteryHistorical = $batteryHistorical->whereIn('hardware_device_id', $solarIds);

        $batteryPercentageAvg = $solarBatteryCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? $solarGeneratorCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? $solarLoadCurrent->whereNotNull('battery_percentage')->avg('battery_percentage')
            ?? 0;

        $batteryFullChargesSum = (float) (
            $solarBatteryHistorical->sum('number_battery_full_charges') > 0
                ? $solarBatteryHistorical->sum('number_battery_full_charges')
                : $solarGeneratorHistorical->sum('number_battery_full_charges')
        );

        // **La tensión de referencia de la instalación.**
        //
        // Comparar amperios medidos a tensiones distintas no significa nada: el
        // panel del Renogy genera a 24 V y el consumo va a 12 V, así que generar
        // 5 A no compensa consumir 5 A —son 10 A referidos a 12 V frente a 5—.
        // Todo lo que se pinte en amperios se lleva antes a esta tensión.
        $referenceVoltage = $this->referenceVoltage($solarIds);

        // Objeto con los cálculos agregados de producción (generador)
        $generator = (object) [
            'current' => round((float) $solarGeneratorCurrent->sum('power')),
            'current_amperage' => round($this->amperageAtReference($solarGeneratorCurrent, $referenceVoltage), 1),
            'current_voltage' => Figures::rounded($solarGeneratorCurrent->avg('voltage') ?? 0, 1),
            'today' => round((float) $solarGeneratorToday->sum('energy_wh')),
            'today_amperage' => round((float) $solarGeneratorToday->sum('energy_wh') / $referenceVoltage),
            'historical' => number_format((float) $solarGeneratorHistorical->sum('energy_wh') / 1000, 1),
            'days_operating' => (int) ($solarGeneratorHistorical->max('days_operating') ?? 0),
            'battery_full_charge' => number_format($batteryFullChargesSum),
            'battery_percentage' => number_format((float) $batteryPercentageAvg),
            'max_light' => number_format((float) ($solarGeneratorCurrent->max('light_brightness') ?? 0)),
            'max_temp' => number_format((float) ($solarGeneratorCurrent->max('temperature') ?? 0), 1),
        ];

        // Objeto con los cálculos agregados de consumo (carga)
        $load = (object) [
            'current' => round((float) $solarLoadCurrent->sum('power')),
            'current_amperage' => number_format($this->amperageAtReference($solarLoadCurrent, $referenceVoltage), 1),
            'current_voltage' => Figures::rounded($solarLoadCurrent->avg('voltage') ?? 0, 1),
            'today' => round((float) $solarLoadToday->sum('energy_wh')),
            'today_amperage' => round((float) $solarLoadToday->sum('energy_wh') / $referenceVoltage),
            'historical' => number_format((float) $solarLoadHistorical->sum('energy_wh') / 1000, 1),
            'battery_percentage' => number_format((float) ($solarLoadCurrent->whereNotNull('battery_percentage')->avg('battery_percentage') ?? 0)),
            'max_temp' => number_format((float) ($solarLoadCurrent->max('temperature') ?? 0), 1),
        ];

        // Objeto con lo que dice el elemento batería
        //
        // Antes no existía: la batería no era un elemento propio y su tensión y
        // su carga se leían de las filas de consumo, que las arrastraban
        // replicadas del esquema viejo. Ahora es el elemento #11 y tiene lo suyo.
        $battery = (object) [
            'current_voltage' => Figures::rounded(
                $solarBatteryCurrent->whereNotNull('voltage')->avg('voltage')
                    ?? $solarLoadCurrent->whereNotNull('voltage')->avg('voltage')
                    ?? 0,
                1
            ),
            'percentage' => number_format((float) $batteryPercentageAvg),
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
                // Las tres tensiones de la instalación, que son distintas: en el
                // montaje real el panel va a 24 V y la batería y el consumo a 12.
                // Esta tarjeta decía «Panel / Batería» y enseñaba la del
                // **consumo** como si fuera la de la batería.
                'title' => 'Panel / Bat. / Consumo',
                'value' => $generator->current_voltage.' / '.$battery->current_voltage.' / '.$load->current_voltage,
                'image' => asset('images/icons/solar-panel.svg'),
                'unit' => 'V',
            ], [
                // Una sola: hay **una** batería. Antes había dos tarjetas,
                // «Bat. Charge» y «Bat. Load», con el mismo porcentaje leído de
                // sitios distintos, porque el esquema viejo replicaba la carga
                // del banco en las filas de generación y de consumo.
                'title' => 'Batería',
                'value' => $battery->percentage,
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

            // Configurado como generador, no "generando algo ahora mismo": de
            // noche un panel solar sigue teniendo sentido enseñar «0 W», pero
            // un dispositivo que nunca tiene generador no debería enseñar la
            // fila en absoluto.
            $hasGenerator = $hw->hardwareEnergy->contains('role', HardwareEnergy::ROLE_GENERATOR);

            return [$hw->id => (object) [
                'generated_now' => (float) $genCurrent->sum('power'),
                'generated_today' => (float) $genToday->sum('energy_wh'),
                'consumed_now' => (float) $loadCurrentDev->sum('power'),
                'consumed_today' => (float) $loadTodayDev->sum('energy_wh'),
                'battery_percentage' => (int) round((float) $batPercentage),
                'days_operating' => (int) ($genHistorical->max('days_operating') ?? 0),
                'generated_historical_kwh' => round((float) $genHistorical->sum('energy_wh') / 1000, 2),
                'consumed_historical_kwh' => round((float) $loadHistoricalDev->sum('energy_wh') / 1000, 2),
                'has_generator' => $hasGenerator,
                // Sólo tiene sentido cuando no hay generador: si lo hay, los
                // tres huecos de "resumen rápido" ya están ocupados por
                // generado/consumido/batería.
                'own_status_badges' => $hasGenerator ? [] : $this->ownStatusBadges($hw),
                'loads' => $this->loadChannels($hw, $loadCurrentDev, $loadTodayDev),
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

    /**
     * Las tres tarjetas de "resumen rápido" cuando el dispositivo no tiene
     * generador: no tiene sentido enseñar "Generado" ni la batería de la
     * instalación solar, así que se enseña lo que el propio dispositivo
     * reporta de sí mismo (D108/estado): CPU, temperatura, su batería propia
     * y memoria, en ese orden de prioridad, las tres primeras que tengan dato.
     *
     * @return list<array{icon: string, color: string, label: string, unit: string, value: float}>
     */
    private function ownStatusBadges(HardwareDevice $hw): array
    {
        $candidates = [
            ['field' => 'cpu', 'icon' => 'memory', 'color' => 'text-violet-600 dark:text-violet-400', 'label' => 'CPU', 'unit' => '%'],
            ['field' => 'temp', 'icon' => 'device_thermostat', 'color' => 'text-orange-600 dark:text-orange-400', 'label' => 'Temp.', 'unit' => '°C'],
            ['field' => 'battery_level', 'icon' => 'battery_full', 'color' => 'text-emerald-600 dark:text-emerald-400', 'label' => 'Batería', 'unit' => '%'],
            ['field' => 'ram', 'icon' => 'sd_card', 'color' => 'text-sky-600 dark:text-sky-400', 'label' => 'RAM', 'unit' => '%'],
        ];

        $badges = [];

        foreach ($candidates as $candidate) {
            $value = $hw->getAttribute($candidate['field']);

            if ($value === null) {
                continue;
            }

            $badges[] = [
                'icon' => $candidate['icon'],
                'color' => $candidate['color'],
                'label' => $candidate['label'],
                'unit' => $candidate['unit'],
                'value' => (float) $value,
            ];

            if (count($badges) === 3) {
                break;
            }
        }

        return $badges;
    }

    /**
     * Un elemento por cada consumo activo del dispositivo, con su propio
     * "ahora"/"hoy": antes se sumaban todos en una única fila, y una nevera y
     * un router en canales distintos del mismo monitor se veían como un solo
     * número sin decir cuál pesaba más.
     *
     * El nombre es el del dispositivo monitorizado si lo tiene, y el canal
     * sólo se nombra cuando hay más de un consumo que distinguir —igual que
     * {@see HardwareEnergy::getDisplayNameAttribute()}.
     *
     * @param  Collection<int, HardwareEnergyReading>  $loadCurrentDev
     * @param  Collection<int, HardwareEnergyToday>  $loadTodayDev
     * @return list<object{label: string, now: float, today: float}>
     */
    private function loadChannels(HardwareDevice $hw, $loadCurrentDev, $loadTodayDev): array
    {
        $loads = $hw->hardwareEnergy->where('role', HardwareEnergy::ROLE_LOAD)->values();
        $several = $loads->count() > 1;

        return $loads->map(function (HardwareEnergy $element) use ($loadCurrentDev, $loadTodayDev, $several) {
            $label = $element->monitorized?->display_name;

            if ($label === null) {
                $label = $several ? 'Canal '.$element->sensor_position : 'Consumo';
            } elseif ($several && $element->sensor_position > 0) {
                $label .= ' · canal '.$element->sensor_position;
            }

            return (object) [
                'label' => $label,
                'now' => (float) $loadCurrentDev->where('hardware_energy_id', $element->id)->sum('power'),
                'today' => (float) $loadTodayDev->where('hardware_energy_id', $element->id)->sum('energy_wh'),
            ];
        })->all();
    }
}
