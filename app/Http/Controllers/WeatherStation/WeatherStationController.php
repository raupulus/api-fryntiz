<?php

namespace App\Http\Controllers\WeatherStation;

use App\Http\Controllers\Controller;
use function route;
use function view;

/**
 * Class SmartPlantController
 *
 * @package App\Http\Controllers\SmartPlantController
 */
class WeatherStationController extends Controller
{

    /**
     * Muestra un resumen para debug de los datos recopilados por los sensores.
     */
    public function index()
    {
        $sections = [
            'Relámpagos' => route('api.wheaterstation.v1.table.lightning'),
            'Temperatura' => route('api.wheaterstation.v1.table.temperature'),
            'Humedad' => route('api.wheaterstation.v1.table.humidity'),
            'Presión' => route('api.wheaterstation.v1.table.pressure'),
            'Viento' => route('api.wheaterstation.v1.table.winter'),
            'Dirección del Viento' => route('api.wheaterstation.v1.table.wind_direction'),
            'Lluvia' => route('api.wheaterstation.v1.table.rain'),
            'Luz' => route('api.wheaterstation.v1.table.light'),
            'Calidad del Aire' => route('api.wheaterstation.v1.table.air_quality'),
            'CO2-ECO2' => route('api.wheaterstation.v1.table.eco2'),
            'TVOC' => route('api.wheaterstation.v1.table.tvoc'),
        ];

        return view('weather_station.index')->with([
            'sections' => $sections,
        ]);
    }

}
