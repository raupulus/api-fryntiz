<?php

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
     * Mapa de sensores con su modelo, título, icono y unidad.
     */
    private const SENSOR_MAP = [
        'temperature' => ['title' => 'Temperatura', 'model' => Temperature::class, 'icon' => 'thermostat', 'unit' => 'ºC'],
        'humidity' => ['title' => 'Humedad', 'model' => Humidity::class, 'icon' => 'humidity_percentage', 'unit' => '%'],
        'pressure' => ['title' => 'Presión', 'model' => Pressure::class, 'icon' => 'speed', 'unit' => 'hPa'],
        'light' => ['title' => 'Luz', 'model' => Light::class, 'icon' => 'light_mode', 'unit' => 'lux'],
        'wind' => ['title' => 'Viento', 'model' => Wind::class, 'icon' => 'air', 'unit' => 'km/h'],
        'wind-direction' => ['title' => 'Dirección del Viento', 'model' => WindDirection::class, 'icon' => 'explore', 'unit' => '°'],
        'rain' => ['title' => 'Lluvia', 'model' => Rain::class, 'icon' => 'water_drop', 'unit' => 'mm'],
        'eco2' => ['title' => 'ECO2', 'model' => Eco2::class, 'icon' => 'co2', 'unit' => 'ppm'],
        'tvoc' => ['title' => 'TVOC', 'model' => Tvoc::class, 'icon' => 'cloud', 'unit' => 'ppb'],
        'air-quality' => ['title' => 'Calidad del Aire', 'model' => AirQuality::class, 'icon' => 'nest_eco_leaf', 'unit' => '%'],
        'lightning' => ['title' => 'Relámpagos', 'model' => Lightning::class, 'icon' => 'bolt', 'unit' => ''],
    ];

    /**
     * Muestra la vista principal con el widget y las tarjetas de sensores.
     */
    public function index()
    {
        $sections = [];

        foreach (self::SENSOR_MAP as $type => $config) {
            $sections[] = [
                'title' => $config['title'],
                'url' => route('weather_station.sensor', $type),
                'icon' => $config['icon'],
            ];
        }

        return view('weather_station.index', compact('sections'));
    }

    /**
     * Muestra la página de datos de un sensor individual con tabla paginada.
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

        // Obtener los últimos 50 registros directamente para la tabla server-side
        $records = $model::whereNotNull('value')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('weather_station.sensor', [
            'title' => $sensor['title'],
            'type' => $type,
            'unit' => $sensor['unit'],
            'icon' => $sensor['icon'],
            'records' => $records,
        ]);
    }
}
