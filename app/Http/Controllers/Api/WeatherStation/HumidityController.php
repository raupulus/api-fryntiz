<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Humidity;

/**
 * Class HumidityController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class HumidityController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Humidity';

    protected static function getModel(): string
    {
        return Humidity::class;
    }
}
