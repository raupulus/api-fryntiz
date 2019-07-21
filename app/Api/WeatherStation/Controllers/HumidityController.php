<?php

namespace App\Http\Controllers;

use App\Humidity;
use function array_fill_keys;
use function array_keys;
use function array_merge;
use Illuminate\Http\Request;
use function response;

class HumidityController extends BaseWheaterStationController
{
    protected $model = '\App\Humidity';
}
