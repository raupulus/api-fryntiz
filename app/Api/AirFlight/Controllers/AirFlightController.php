<?php

namespace App\Api\AirFlight\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function GuzzleHttp\json_decode;
use function response;
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

    public function addJson(Request $request) {
        $data = json_decode($request->get('data'));

        $fallidos = 0;

        return response()->json([
            'status' => '200',
            'data' => $data
        ], 200);
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
