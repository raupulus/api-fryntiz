<?php

namespace App\Http\Controllers;

use App\Pressure;
use App\Temperature;
use Illuminate\Http\Request;
use function response;

class TemperatureController extends BaseWheaterStationController
{
    protected $model = '\App\Temperature';
}
