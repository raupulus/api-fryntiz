<?php

namespace App\Http\Controllers;

use App\Pressure;
use Illuminate\Http\Request;
use function response;

class PressureController extends BaseWheaterStationController
{
    protected $model = '\App\Pressure';
}
