<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Temperature;

/**
 * Class TemperatureController
 */
class TemperatureController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Temperature';

    protected static function getModel(): string
    {
        return Temperature::class;
    }
}
