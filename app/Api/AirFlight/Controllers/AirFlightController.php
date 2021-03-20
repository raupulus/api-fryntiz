<?php

namespace App\Api\AirFlight\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
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

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($data)
    {
        return Validator::make($data, [
            ## Avión
            'icao' => 'required|string',
            'category' => 'nullable|string',

            ## Vuelo
            'squawk' => 'required|string',
            'flight' => 'required|string',
            'lat' => 'nullable|string',
            'lon' => 'nullable|string',
            //'seen_pos' => 'required|timestamp',
            'altitude' => 'nullable|numeric',
            'vert_rate' => 'nullable|numeric',
            'track' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'messages' => 'nullable|numeric',
            'seen_at' => 'required|timestamp',
            'rssi' => 'required|numeric',
        ])->validate();
    }
}
