<?php

namespace App\Api\AirFlight\Controllers;

use App\Http\Controllers\Controller;
use function view;

/**
 * Class AirFlightController
 *
 * @package App\Http\Controllers\AirFlight
 */
class AirFlightController extends Controller
{
    public function index()
    {
        return view('airflight.index');
    }
}
