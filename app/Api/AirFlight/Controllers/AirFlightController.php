<?php

namespace App\Api\AirFlight\Controllers;

use App\Api\AirFlight\AirFlightAirPlane;
use App\Api\AirFlight\AirFlightRoutes;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function GuzzleHttp\json_decode;
use function response;
use function view;
use Illuminate\Support\Facades\Log;

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
        //return response()->json(['res' => $request->get('data')]);
        Log::error('TEST:');
        Log::error($request->get('data'));
        $data = json_decode($request->get('data'));

        //return response()->json(['ok' => true]);
        $fails = 0;

        ## Proceso cada dato recibido mediante JSON.
        foreach ($data as $d) {


            try {
                ## Parseo la fecha
                if ($d->seen_at) {
                    $d->seen_at = (new \DateTime($d->seen_at))->format('Y-m-d H:i:s');
                }

                // TODO → Corregir fallo al subir cuando se está validando

                ## Obtengo atributos y los validos para excluir posible basura.
                //$attributes = $this->addValidate(get_object_vars($d));
                //$model->fill($attributes);
                // TEMPORAL:
                $z = get_object_vars($d);
                if (is_array($z)) {
                    $airflight = AirFlightAirPlane::updateOrCreate([
                        'icao' => $d->icao,
                        'category' => $d->category,
                    ],
                        [
                            'seen_last_at' => $d->seen_at
                        ]
                    );

                    if (! $airflight->seen_first_at) {
                        $airflight->seen_first_at = $d->seen_at;
                        $airflight->save();
                    }

                    ## Solo almaceno rutas cuando hay latitud y longitud.
                    if ($d->lat && $d->lon) {
                        $route = AirFlightRoutes::updateOrCreate(
                            [
                                'airflight_airplane_id' => $airflight->id,
                                'lat' => $d->lat,
                                'lon' => $d->lon,
                                'seen_at' => $d->seen_at,
                            ],
                            [
                                'airflight_airplane_id' => $airflight->id,
                                'squawk' => $d->squawk,
                                'flight' => $d->flight,
                                'lat' => $d->lat,
                                'lon' => $d->lon,
                                'altitude' => $d->altitude,
                                'vert_rate' => $d->vert_rate,
                                'track' => $d->track,
                                'speed' => $d->speed,
                                'seen_at' => $d->seen_at,
                                'messages' => $d->messages,
                                'rssi' => $d->rssi,
                                'emergency' => $d->emergency,
                            ]
                        );
                    }

                }

                //$model->save();
            } catch (Exception $e) {
                Log::error('Error insertando airflight');
                Log::error($e);
                $fails++;
            }
        }

        ## Respuesta cuando se ha guardado el modelo correctamente
        if ($fails == 0) {
            return response()->json('Guardado Correctamente', 201);
        } else if ($fails >= 1) {
            return response()->json('Fallidos: ' . $fails, 200);
        }

        return response()->json('No se ha guardado nada', 500);
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
            'squawk' => 'nullable|string',
            'flight' => 'nullable|string',
            'lat' => 'nullable|string',
            'lon' => 'nullable|string',
            'altitude' => 'nullable|numeric',
            'vert_rate' => 'nullable|numeric',
            'track' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'messages' => 'nullable|numeric',
            'seen_at' => 'required|timestamp',
            'rssi' => 'required|numeric',
            'emergency' => 'nullable|string',
        ])->validate();
    }
}
