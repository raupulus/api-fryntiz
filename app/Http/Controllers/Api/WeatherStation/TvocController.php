<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Tvoc;

/**
 * Class TvocController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class TvocController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Tvoc';

    protected static function getModel(): string
    {
        return Tvoc::class;
    }
}
