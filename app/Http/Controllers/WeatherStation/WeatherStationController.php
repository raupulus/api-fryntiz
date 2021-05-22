<?php

namespace App\Http\Controllers\WeatherStation;

use App\Http\Controllers\Controller;
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
        return view('weather_station.index')->with([

        ]);
    }

}
