<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Rain;

/**
 * Class RainController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class RainController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Rain';

    protected static function getModel(): string
    {
        return Rain::class;
    }
}
