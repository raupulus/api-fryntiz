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
            'Luz' => route('api.wheaterstation.v1.table.light'),
            'Indice-UV' => route('api.wheaterstation.v1.table.uv'),
            'UVA' => route('api.wheaterstation.v1.table.uva'),
            'UVB' => route('api.wheaterstation.v1.table.uvb'),
            'CO2-ECO2' => route('api.wheaterstation.v1.table.eco2'),
            'TVOC' => route('api.wheaterstation.v1.table.tvoc'),
            'Calidad del Aire' => route('api.wheaterstation.v1.table.air_quality'),
        ];

        return view('weather_station.index')->with([
            'sections' => $sections,
        ]);
    }

}
