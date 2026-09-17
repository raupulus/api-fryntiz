<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hardware;

use App\DTO\Hardware\PublicHardwareDeviceData;
use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

use function abort_unless;
use function count;
use function max;
use function min;
use function round;
use function rtrim;
use function ucfirst;
use function view;

/**
 * Controlador para la visualización pública del parque de hardware.
 *
 * Expone el escaparate de hardware y telemetría de salud, aplicando
 * estrictamente Privacy by Design para no filtrar nunca datos sensibles
 * como IPs, números de serie o metadatos de red privada.
 */
class HardwareDeviceController extends Controller
{
    /**
     * Catálogo público de dispositivos hardware.
     */
    public function index(): View
    {
        $devices = HardwareDevice::public()
            ->with([
                'type',
                'components.availableComponent',
                'image.fileType',
            ])
            ->get();

        $deviceDtos = $devices
            ->map(static fn (HardwareDevice $device): PublicHardwareDeviceData => PublicHardwareDeviceData::fromModel($device))
            ->sort(static function (PublicHardwareDeviceData $a, PublicHardwareDeviceData $b): int {
                // Conectados primero (true > false)
                if ($a->isOnline !== $b->isOnline) {
                    return $b->isOnline <=> $a->isOnline;
                }

                // Desempate alfabético por nombre
                return strcasecmp($a->displayName, $b->displayName);
            })
            ->values();

        $types = $deviceDtos
            ->pluck('typeName')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $totalCount = $deviceDtos->count();
        $onlineCount = $deviceDtos->where('isOnline', true)->count();

        return view('hardware.index', [
            'devices' => $deviceDtos,
            'types' => $types,
            'totalCount' => $totalCount,
            'onlineCount' => $onlineCount,
        ]);
    }

    /**
     * Ficha técnica detallada de un dispositivo público.
     */
    public function show(HardwareDevice $device): View
    {
        abort_unless($device->is_public, 404);

        $device->loadMissing([
            'type',
            'components.availableComponent',
            'image.fileType',
        ]);

        $deviceDto = PublicHardwareDeviceData::fromModel($device);
        $energyData = $this->getEnergyData($device);

        return view('hardware.show', [
            'device' => $deviceDto,
            'energyData' => $energyData,
        ]);
    }

    /**
     * Resumen energético de los últimos 7 días para dispositivos con monitorización.
     *
     * @return array{
     *     has_generator: bool,
     *     total_generated_wh: float,
     *     total_consumed_wh: float,
     *     avg_consumed_wh: float,
     *     avg_generated_wh: float,
     *     max_day_wh: float,
     *     load_channels: list<array{id: int, name: string, color_bar: string, color_text: string, color_dot: string}>,
     *     days: list<array{
     *         date: string,
     *         label: string,
     *         generated_wh: float,
     *         generated_pct: float,
     *         consumed_wh: float,
     *         consumed_pct: float,
     *         channels: list<array{id: int, name: string, color_bar: string, color_text: string, wh: float, pct: float}>
     *     }>
     * }|null
     */
    private function getEnergyData(HardwareDevice $device): ?array
    {
        $activeEnergyElements = HardwareEnergy::query()
            ->where(function (Builder $query) use ($device) {
                $query->where('hardware_device_id', $device->id)
                    ->orWhere('hardware_device_monitorized_id', $device->id);
            })
            ->where('is_active', true)
            ->with('monitorized')
            ->orderBy('sensor_position')
            ->get();

        $generatorElements = $activeEnergyElements->where('role', HardwareEnergy::ROLE_GENERATOR)->values();
        $loadElements = $activeEnergyElements->where('role', HardwareEnergy::ROLE_LOAD)->values();

        if ($generatorElements->isEmpty() && $loadElements->isEmpty()) {
            return null;
        }

        $elementIds = $activeEnergyElements->pluck('id')->all();
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();

        $records = HardwareEnergyToday::query()
            ->whereIn('hardware_energy_id', $elementIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $hasAnyData = $records->isNotEmpty()
            || HardwareEnergyToday::query()->whereIn('hardware_energy_id', $elementIds)->exists()
            || HardwareEnergyReading::query()->whereIn('hardware_energy_id', $elementIds)->exists();

        if (! $hasAnyData) {
            return null;
        }

        $channelColors = [
            ['bar' => 'bg-sky-500 dark:bg-sky-400', 'text' => 'text-sky-600 dark:text-sky-400', 'dot' => 'bg-sky-500'],
            ['bar' => 'bg-violet-500 dark:bg-violet-400', 'text' => 'text-violet-600 dark:text-violet-400', 'dot' => 'bg-violet-500'],
            ['bar' => 'bg-emerald-500 dark:bg-emerald-400', 'text' => 'text-emerald-600 dark:text-emerald-400', 'dot' => 'bg-emerald-500'],
            ['bar' => 'bg-rose-500 dark:bg-rose-400', 'text' => 'text-rose-600 dark:text-rose-400', 'dot' => 'bg-rose-500'],
        ];

        $hasMultipleLoads = $loadElements->count() > 1;
        $loadChannels = [];
        foreach ($loadElements as $idx => $el) {
            $colors = $channelColors[$idx % count($channelColors)];
            $name = 'Consumo';
            if ($hasMultipleLoads) {
                if ($el->monitorized && $el->monitorized->id !== $device->id) {
                    $name = $el->monitorized->display_name.($el->sensor_position > 0 ? ' (C'.$el->sensor_position.')' : '');
                } else {
                    $name = 'Canal '.$el->sensor_position;
                }
            }

            $loadChannels[] = [
                'id' => (int) $el->id,
                'name' => $name,
                'color_bar' => $colors['bar'],
                'color_text' => $colors['text'],
                'color_dot' => $colors['dot'],
            ];
        }

        $generatorIds = $generatorElements->pluck('id')->all();
        $hasGenerator = ! empty($generatorIds);

        $totalGenerated = 0.0;
        $totalConsumed = 0.0;
        $maxDayVal = 0.0;
        $rawDays = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $dateStr = $d->toDateString();

            $dayRecords = $records->filter(fn ($r) => Carbon::parse($r->date)->toDateString() === $dateStr);

            $genWh = $hasGenerator
                ? (float) $dayRecords->whereIn('hardware_energy_id', $generatorIds)->sum('energy_wh')
                : 0.0;

            $dayChannels = [];
            $consumedWh = 0.0;
            foreach ($loadChannels as $ch) {
                $chWh = (float) $dayRecords->where('hardware_energy_id', $ch['id'])->sum('energy_wh');
                $consumedWh += $chWh;
                $dayChannels[] = [
                    'id' => $ch['id'],
                    'name' => $ch['name'],
                    'color_bar' => $ch['color_bar'],
                    'color_text' => $ch['color_text'],
                    'wh' => round($chWh, 1),
                ];
            }

            $totalGenerated += $genWh;
            $totalConsumed += $consumedWh;

            $dayMax = $hasGenerator ? max($genWh, $consumedWh) : $consumedWh;
            if ($dayMax > $maxDayVal) {
                $maxDayVal = $dayMax;
            }

            $label = $d->isToday()
                ? 'Hoy'
                : ($d->isYesterday() ? 'Ayer' : ucfirst(rtrim($d->locale('es')->isoFormat('ddd D'), '.')));

            $rawDays[] = [
                'date' => $dateStr,
                'label' => $label,
                'gen_wh' => round($genWh, 1),
                'consumed_wh' => round($consumedWh, 1),
                'channels' => $dayChannels,
            ];
        }

        $scaleMax = $maxDayVal > 0 ? $maxDayVal : 1.0;
        $days = [];
        foreach ($rawDays as $raw) {
            $channels = [];
            foreach ($raw['channels'] as $ch) {
                $channels[] = [
                    'id' => $ch['id'],
                    'name' => $ch['name'],
                    'color_bar' => $ch['color_bar'],
                    'color_text' => $ch['color_text'],
                    'wh' => $ch['wh'],
                    'pct' => $ch['wh'] > 0
                        ? min(100.0, max(4.0, round(($ch['wh'] / $scaleMax) * 100, 1)))
                        : 0.0,
                ];
            }

            $days[] = [
                'date' => $raw['date'],
                'label' => $raw['label'],
                'generated_wh' => $raw['gen_wh'],
                'generated_pct' => $raw['gen_wh'] > 0
                    ? min(100.0, max(4.0, round(($raw['gen_wh'] / $scaleMax) * 100, 1)))
                    : 0.0,
                'consumed_wh' => $raw['consumed_wh'],
                'consumed_pct' => $raw['consumed_wh'] > 0
                    ? min(100.0, max(4.0, round(($raw['consumed_wh'] / $scaleMax) * 100, 1)))
                    : 0.0,
                'channels' => $channels,
            ];
        }

        return [
            'has_generator' => $hasGenerator,
            'total_generated_wh' => round($totalGenerated, 1),
            'total_consumed_wh' => round($totalConsumed, 1),
            'avg_consumed_wh' => round($totalConsumed / 7, 1),
            'avg_generated_wh' => round($totalGenerated / 7, 1),
            'max_day_wh' => round($maxDayVal, 1),
            'load_channels' => $loadChannels,
            'days' => $days,
        ];
    }
}
