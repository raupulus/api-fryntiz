<?php

declare(strict_types=1);

namespace App\Http\Controllers\WeatherStation;

use App\Http\Controllers\Controller;
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
    private const SENSOR_MAP = [
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
            'title' => 'Luz', 'model' => Light::class, 'icon' => 'light_mode',
            'primary' => ['field' => 'lux', 'unit' => 'lux'],
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
     * Muestra la vista principal con el widget y las tarjetas de sensores,
     * cada una con el valor principal (y secundario si aplica) del último
     * registro recibido.
     */
    public function index()
    {
        $sections = [];

        foreach (self::SENSOR_MAP as $type => $config) {
            $model = $config['model'];
            $primaryField = $config['primary']['field'];

            $latest = $model::whereNotNull($primaryField)
                ->latest('created_at')
                ->first();

            $sections[] = [
                'title' => $config['title'],
                'url' => route('weather_station.sensor', $type),
                'icon' => $config['icon'],
                'primary' => $latest ? $this->formatSensorValue($latest, $config['primary']) : null,
                'secondary' => ($latest && isset($config['secondary']))
                    ? $this->formatSensorValue($latest, $config['secondary'])
                    : null,
            ];
        }

        return view('weather_station.index', compact('sections'));
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
    public function sensor(string $type)
    {
        if (! isset(self::SENSOR_MAP[$type])) {
            abort(404, 'Sensor no encontrado');
        }

        $sensor = self::SENSOR_MAP[$type];
        $model = $sensor['model'];
        $primaryField = $sensor['primary']['field'];

        $columns = $model::getTableHeads();
        unset($columns['id'], $columns['created_at']);

        $cellsInfo = $model::getTableCellsInfo();

        $records = $model::whereNotNull($primaryField)
            ->orderByDesc('created_at')
            ->select(array_merge(array_keys($columns), ['created_at']))
            ->paginate(25);

        if (isset($sensor['convertFields'], $sensor['convertMethod'])) {
            $records->getCollection()->transform(function ($record) use ($sensor) {
                foreach ($sensor['convertFields'] as $field) {
                    $record->{$field} = call_user_func($sensor['convertMethod'], $record->{$field});
                }

                return $record;
            });
        }

        $types = array_keys(self::SENSOR_MAP);
        $nextType = $types[(array_search($type, $types) + 1) % count($types)];

        return view('weather_station.sensor', [
            'title' => $sensor['title'],
            'type' => $type,
            'icon' => $sensor['icon'],
            'columns' => $columns,
            'cellsInfo' => $cellsInfo,
            'nextTitle' => self::SENSOR_MAP[$nextType]['title'],
            'nextUrl' => route('weather_station.sensor', $nextType),
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
