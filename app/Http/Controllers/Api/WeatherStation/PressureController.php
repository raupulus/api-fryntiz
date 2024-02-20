<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Pressure;

/**
 * Class PressureController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class PressureController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Pressure';

    protected static function getModel(): string
    {
        return Pressure::class;
    }
}
