<?php

declare(strict_types=1);

namespace App\Http\Controllers\WeatherStation;

use App\Enums\HardwareLocationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class WeatherStationController
 *
 * Controlador web para las vistas públicas de la estación meteorológica.
 */
class WeatherStationController extends Controller
{
    /**
     * Mapa de sensores con su modelo, título e icono.
     *
     * `primary`/`secondary` describen qué campo (y con qué unidad) se
     * muestra en la tarjeta resumen de la vista índice, usando el último
     * registro de cada sensor.
     */
    public const SENSOR_MAP = [
        'temperature' => [
            'title' => 'Temperatura', 'model' => Temperature::class, 'icon' => 'thermostat',
            'primary' => ['field' => 'value', 'unit' => 'ºC'],
        ],
        'humidity' => [
            'title' => 'Humedad', 'model' => Humidity::class, 'icon' => 'humidity_percentage',
            'primary' => ['field' => 'value', 'unit' => '%'],
        ],
        'pressure' => [
            'title' => 'Presión', 'model' => Pressure::class, 'icon' => 'speed',
            'primary' => ['field' => 'value', 'unit' => 'hPa'],
        ],
        'light' => [
            // `lumens` es el único campo obligatorio del sensor de luz (ver
            // Light::getFieldsValidation()); `lux` es opcional y hay
            // estaciones (ej. RPI WS Luz) que nunca lo calculan, así que no
            // puede ser el campo "primary" o esas estaciones se quedan sin
            // tarjeta ni datos en el listado.
            'title' => 'Luz', 'model' => Light::class, 'icon' => 'light_mode',
            'primary' => ['field' => 'lumens', 'unit' => 'lm'],
            'secondary' => ['field' => 'index', 'unit' => 'índice'],
        ],
        'wind' => [
            'title' => 'Viento', 'model' => Wind::class, 'icon' => 'air',
            'primary' => ['field' => 'speed', 'unit' => 'km/h', 'transform' => [Wind::class, 'msToKmh']],
            'secondary' => ['field' => 'max', 'unit' => 'km/h ráfaga', 'transform' => [Wind::class, 'msToKmh']],
            // Columnas de la tabla de detalle que también deben convertirse de m/s a km/h.
            'convertFields' => ['speed', 'average', 'min', 'max'],
            'convertMethod' => [Wind::class, 'msToKmh'],
        ],
        'wind-direction' => [
            'title' => 'Dirección del Viento', 'model' => WindDirection::class, 'icon' => 'explore',
            'primary' => ['field' => 'direction', 'unit' => ''],
            'secondary' => ['field' => 'grades', 'unit' => 'º'],
        ],
        'rain' => [
            'title' => 'Lluvia', 'model' => Rain::class, 'icon' => 'water_drop',
            'primary' => ['field' => 'rain', 'unit' => 'mm'],
            'secondary' => ['field' => 'rain_intensity', 'unit' => 'mm/h'],
        ],
        'eco2' => [
            'title' => 'ECO2', 'model' => Eco2::class, 'icon' => 'co2',
            'primary' => ['field' => 'value', 'unit' => 'ppm'],
        ],
        'tvoc' => [
            'title' => 'TVOC', 'model' => Tvoc::class, 'icon' => 'cloud',
            'primary' => ['field' => 'value', 'unit' => 'ppb'],
        ],
        'air-quality' => [
            'title' => 'Calidad del Aire', 'model' => AirQuality::class, 'icon' => 'nest_eco_leaf',
            'primary' => ['field' => 'air_quality', 'unit' => '%'],
            'secondary' => ['field' => 'gas_resistance', 'unit' => 'Ω'],
        ],
        'lightning' => [
            'title' => 'Relámpagos', 'model' => Lightning::class, 'icon' => 'bolt',
            'primary' => ['field' => 'distance', 'unit' => 'km'],
            'secondary' => ['field' => 'energy', 'unit' => ''],
        ],
    ];

    /**
     * Muestra la vista principal con el widget y las estaciones agrupadas por
     * ubicación (interior/exterior) y zona. Cada estación muestra las tarjetas
     * de sus sensores con el valor principal (y secundario si aplica) del
     * último registro recibido en ese dispositivo.
     *
     * Si todavía no hay ninguna estación clasificada (sin `location_type`), se
     * muestra el resumen global de sensores como comportamiento de reserva.
     */
    public function index()
    {
        $stations = HardwareDevice::weatherStations()
            ->orderBy('zone')
            ->orderBy('id')
            ->get();

        $mainStationId = app(WeatherStationService::class)
            ->resolveMainStationId();

        $groups = [];

        foreach (HardwareLocationTypeEnum::cases() as $type) {
            $inType = $stations->where('location_type', $type);

            if ($inType->isEmpty()) {
                continue;
            }

            $zones = [];

            foreach ($inType->groupBy(fn (HardwareDevice $d) => $d->zone ?: 'Sin zona') as $zoneName => $devices) {
                $zones[] = [
                    'zone' => $zoneName,
                    'stations' => $devices->map(fn (HardwareDevice $d) => [
                        'id' => $d->getKey(),
                        'name' => $d->display_name,
                        'is_main' => $d->getKey() === $mainStationId,
                        'sections' => $this->buildSensorCards($d->getKey()),
                    ])->values()->all(),
                ];
            }

            $groups[] = [
                'location_type' => $type->value,
                'label' => $type->label(),
                'zones' => $zones,
            ];
        }

        // Reserva: sin estaciones clasificadas mostramos el resumen global.
        $ungrouped = empty($groups) ? $this->buildSensorCards(null) : [];

        return view('weather_station.index', compact('groups', 'ungrouped', 'mainStationId'));
    }

    /**
     * Construye las tarjetas de sensores (valor principal/secundario del último
     * registro) opcionalmente filtradas por una estación concreta.
     *
     * Los sensores sin ningún registro se omiten: no tiene sentido enlazar a
     * una vista de detalle vacía (una estación puede tener solo viento, o solo
     * luz/radiación, y nunca rellenar el resto de sensores).
     *
     * @param  int|null  $stationId  Dispositivo del que tomar las lecturas.
     * @return array<int, array<string, mixed>>
     */
    private function buildSensorCards(?int $stationId): array
    {
        $sections = [];

        foreach (self::SENSOR_MAP as $type => $config) {
            $model = $config['model'];
            $primaryField = $config['primary']['field'];

            $latest = $model::whereNotNull($primaryField)
                ->when($stationId, fn ($q) => $q->where('hardware_device_id', $stationId))
                ->latest('created_at')
                ->first();

            if (! $latest) {
                continue;
            }

            $sections[] = [
                'title' => $config['title'],
                'url' => route('weather_station.sensor', array_filter([
                    'type' => $type,
                    'station' => $stationId,
                ])),
                'icon' => $config['icon'],
                'primary' => $this->formatSensorValue($latest, $config['primary']),
                'secondary' => isset($config['secondary'])
                    ? $this->formatSensorValue($latest, $config['secondary'])
                    : null,
            ];
        }

        return $sections;
    }

    /**
     * Indica si un tipo de sensor tiene al menos un registro, opcionalmente
     * filtrado por estación.
     */
    private function sensorHasData(string $type, ?int $stationId): bool
    {
        $config = self::SENSOR_MAP[$type];
        $model = $config['model'];
        $primaryField = $config['primary']['field'];

        return $model::whereNotNull($primaryField)
            ->when($stationId, fn ($q) => $q->where('hardware_device_id', $stationId))
            ->exists();
    }

    /**
     * Busca cíclicamente, a partir del sensor actual, el siguiente tipo con
     * datos disponibles para la estación indicada (o de forma global si no hay
     * estación). Si ninguno tiene datos, devuelve el propio tipo actual.
     */
    private function nextTypeWithData(string $currentType, ?int $stationId): string
    {
        $types = array_keys(self::SENSOR_MAP);
        $start = array_search($currentType, $types);
        $count = count($types);

        for ($i = 1; $i < $count; $i++) {
            $candidate = $types[($start + $i) % $count];

            if ($this->sensorHasData($candidate, $stationId)) {
                return $candidate;
            }
        }

        return $currentType;
    }

    /**
     * Muestra la página de datos de un sensor individual con tabla paginada.
     *
     * Las columnas mostradas son las definidas por el propio modelo en
     * `getTableHeads()`/`getTableCellsInfo()`, para no duplicar aquí los
     * campos de cada sensor.
     *
     * @param  string  $type  Tipo de sensor (temperature, humidity, pressure, etc.)
     * @return View
     */
    public function sensor(Request $request, string $type)
    {
        if (! isset(self::SENSOR_MAP[$type])) {
            abort(404, 'Sensor no encontrado');
        }

        $stationId = $request->filled('station') ? (int) $request->query('station') : null;
        $station = $stationId ? HardwareDevice::find($stationId) : null;

        // Sin datos para este sensor en esta estación (o de forma global): no
        // tiene sentido servir una vista vacía. Puede ocurrir porque la
        // estación nunca rellenará ese sensor (EJ: una estación solo de viento).
        if (! $this->sensorHasData($type, $stationId)) {
            return redirect()
                ->route('weather_station.index')
                ->with('notice', 'Ese sensor no tiene datos disponibles todavía.');
        }

        $sensor = self::SENSOR_MAP[$type];
        $model = $sensor['model'];
        $primaryField = $sensor['primary']['field'];

        $columns = $model::getTableHeads();
        unset($columns['id'], $columns['created_at']);

        $cellsInfo = $model::getTableCellsInfo();

        $records = $model::whereNotNull($primaryField)
            ->when($stationId, fn ($q) => $q->where('hardware_device_id', $stationId))
            ->orderByDesc('created_at')
            ->select(array_merge(array_keys($columns), ['created_at']))
            ->paginate(25)
            ->withQueryString();

        if (isset($sensor['convertFields'], $sensor['convertMethod'])) {
            $records->getCollection()->transform(function ($record) use ($sensor) {
                foreach ($sensor['convertFields'] as $field) {
                    $record->{$field} = call_user_func($sensor['convertMethod'], $record->{$field});
                }

                return $record;
            });
        }

        // "Siguiente" salta los sensores sin datos para esta misma estación,
        // para no llevar al usuario a un tipo que rebotaría al índice.
        $nextType = $this->nextTypeWithData($type, $stationId);

        return view('weather_station.sensor', [
            'title' => $sensor['title'],
            'type' => $type,
            'icon' => $sensor['icon'],
            'columns' => $columns,
            'cellsInfo' => $cellsInfo,
            'stationName' => $station?->display_name,
            'nextTitle' => self::SENSOR_MAP[$nextType]['title'],
            'nextUrl' => route('weather_station.sensor', array_filter([
                'type' => $nextType,
                'station' => $stationId,
            ])),
            'records' => $records,
        ]);
    }

    /**
     * Formatea el valor de un campo del último registro para mostrarlo en
     * la tarjeta resumen: redondea números a 1 decimal y añade la unidad.
     */
    private function formatSensorValue(object $record, array $field): string
    {
        $value = $record->{$field['field']};

        if (isset($field['transform'])) {
            $value = call_user_func($field['transform'], $value);
        }

        if (is_numeric($value)) {
            $value = rtrim(rtrim(sprintf('%.1f', (float) $value), '0'), '.');
        }

        $unit = $field['unit'] ?? '';

        return trim($value.($unit !== '' ? ' '.$unit : ''));
    }
}
