<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Eco2;

/**
 * Class Eco2Controller
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class Eco2Controller extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Eco2';

    protected static function getModel(): string
    {
        return Eco2::class;
    }
}
