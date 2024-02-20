<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\WindDirection;

/**
 * Class WindDirectionController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class WindDirectionController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\WindDirection';

    protected static function getModel(): string
    {
        return WindDirection::class;
    }
}
