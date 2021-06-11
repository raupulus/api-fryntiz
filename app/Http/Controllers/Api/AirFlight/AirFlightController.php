<?php

namespace App\Http\Controllers\Api\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function abort;
use function auth;
use function base_path;
use function GuzzleHttp\json_decode;
use function public_path;
use function random_int;
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
    public function getAircraftHistory(Request $request)
    {

    }

    /**
     * Devuelve datos de la db para los vuelos.
     *
     * @param \Illuminate\Http\Request $request
     * @param                string    $data Temporalmente el archivo a
     *                                       devolver.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFromDb(Request $request, $data)
    {
        return response()->file(public_path('resources/airflight/db/' . $data . '.json'));
    }

    /**
     * Devuelve información del receptor emulado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReceiverInformation()
    {
        return response()->json([
            'history' => 12,
            'lat' => 36.7381,
            'lon' =>  -6.4301,
            'refresh' => 10000,
            'version' => 'dump978 5.0'
        ]);
    }

    /**
     * Devuelve los últimos aviones en una respuesta json.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAircraftjson(Request $request) {
        $now = Carbon::now();

        $lastCheckTimestampMs = $request->get('_');
        $historyPage = $request->get('history');

        if ($historyPage) {
            $checkFrom = (clone($now))->subMinutes($historyPage*5);

            if ($historyPage > 0) {
                $checkTo = (clone($now))->subMinutes(($historyPage*5) - 1);
            }

        } else if ($lastCheckTimestampMs) {
            try {
                $checkFrom = Carbon::createFromTimestampMsUTC($lastCheckTimestampMs);
            } catch (Exception $e) {
                $checkFrom = (clone($now))->subMinutes(60);
            }
        } else {
            $checkFrom = (clone($now))->subDays(60);
        }


        $aircrafts = AirFlightAirPlane::select([
                'airflight_airplanes.icao as hex',
                'airflight_airplanes.category',
                'airflight_airplanes.seen_last_at as seen_at',
                // TODO → Espera que se devuelva un campo "age" o "seen" ???
                'airflight_routes.squawk',
                'airflight_routes.flight',
                'airflight_routes.lat',
                'airflight_routes.lon',
                'airflight_routes.altitude',
                'airflight_routes.vert_rate',
                'airflight_routes.track',
                'airflight_routes.speed',
                'airflight_routes.rssi',
                'airflight_routes.emergency',
                'airflight_routes.messages',
            ])
            ->leftJoin('airflight_routes', 'airflight_routes.airplane_id', 'airflight_airplanes.id')
            //->where('airflight_airplanes.seen_last_at', '>=', $checkFrom)
            ->where('airflight_routes.created_at', '>=', $checkFrom)
            ;

        if (isset($checkTo) && $checkTo) {
            $aircrafts->where('airflight_routes.created_at', '<=', $checkTo);
        }


        $aircrafts = $aircrafts
            ->orderByDesc('airflight_airplanes.seen_last_at')
            ->orderByDesc('airflight_routes.seen_at')
            ->get();

        $aircrafts->map(function ($ele) use ($now) {
            try {
                $seenAt = Carbon::createFromFormat('Y-m-d H:i:s', $ele->seen_at);
                $ele->seen_at = $seenAt->getTimestamp();
                $ele->seen = $seenAt->diffInSeconds($now);
            } catch (Exception $e) {
                $ele->seen_at = 0;
                $ele->seen = 0;
            }

            return $ele;
        });

        /*
         { "now" : 1623358369.4,
              "messages" : 121565,
              "aircraft" : [
                {"hex":"34510f","flight":"SWT185  ","alt_baro":20000,"alt_geom":21150,"gs":283.1,"ias":194,"tas":268,"mach":0.428,"track":20.0,"roll":0.7,"mag_heading":13.4,"baro_rate":0,"geom_rate":0,"squawk":"4466","emergency":"none","category":"A2","nav_qnh":1012.8,"nav_altitude_fms":20000,"lat":36.789116,"lon":-5.880082,"nic":8,"rc":186,"seen_pos":40.5,"version":2,"nic_baro":1,"nac_p":9,"nac_v":2,"sil":3,"sil_type":"perhour","gva":2,"sda":2,"mlat":[],"tisb":[],"messages":1877,"seen":12.6,"rssi":-26.0}
              ]
            }
         */

        /*
        return response()->json([
            'now' => (Carbon::now())->getTimestamp(),
            'messages' => $aircrafts->sum('messages'),
            'aircraft' => [
                "hex" => "34510f",
                "flight" => "SWT185  ",
                "alt_baro" => 20000,
                "alt_geom" => 21150,
                "gs" => 283.1,
                "ias" => 194,
                "tas" => 268,
                "mach" => 0.428,
                "track" => 20,
                "roll" => 0.7,
                "mag_heading" => 13.4,
                "baro_rate" => 0,
                "geom_rate" => 0,
                "squawk" => "4466",
                "emergency" => "none",
                "category" => "A2",
                "nav_qnh" => 1012.8,
                "nav_altitude_fms" => 20000,
                "lat" => 36.789116,
                "lon" => -6.010082,
                "nic" => 8,
                "rc" => 186,
                "seen_pos" => 20.5,
                "version" => 2,
                "nic_baro" => 1,
                "nac_p" => 9,
                "nac_v" => 2,
                "sil" => 3,
                "sil_type" => "perhour",
                "gva" => 2,
                "sda" => 2,
                "mlat" => [
                ],
                "tisb" => [
                ],
                "messages" => 1877,
                "seen" => 0.1,
                "rssi" => -26
            ]
        ]);
        */

        return response()->json([
            'now' => (Carbon::now())->getTimestamp(),
            'messages' => $aircrafts->sum('messages'),
            'aircraft' => $aircrafts->toArray(),
        ]);
    }

    public function addJson(Request $request) {
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
                    $airflight = AirFlightAirPlane::updateOrCreate(
                        [
                            'icao' => $d->icao,
                        ],
                        [
                            'seen_last_at' => $d->seen_at,
                        ]
                    );

                    if (!$airflight->category) {
                        $airflight->category = $d->category;
                        $airflight->save();
                    }

                    if (!$airflight->user_id) {
                        $airflight->user_id = auth()->id();
                        $airflight->save();
                    }

                    ## Comprueba si no se marcó al verse por primera vez o hay fecha anterior.
                    if (! $airflight->seen_first_at || ($airflight->seen_first_at > $d->seen_at)) {
                        $airflight->seen_first_at = $d->seen_at;
                        $airflight->save();
                    }

                    ## Solo almaceno rutas cuando hay latitud y longitud.
                    if ($d->lat && $d->lon) {
                        $lastHour = (Carbon::now())->subHour()->format('Y-m-d H:i:s');
                        $lastSeenRoute = AirFlightRoute::where('seen_at', '<=', $lastHour )
                            ->where('airplane_id', $airflight->id)
                            ->where('lat', $d->lat)
                            ->where('lon', $d->lon)
                            ->orderByDesc('seen_at')
                            ->first();
                        $lastSeen = $lastSeenRoute ? $lastSeenRoute->seen_at : null;

                        $route = AirFlightRoute::updateOrCreate(
                            [
                                'airplane_id' => $airflight->id,
                                'lat' => $d->lat,
                                'lon' => $d->lon,
                                'seen_at' => $lastSeen,
                            ],
                            [
                                'user_id' => auth()->id(),
                                'airplane_id' => $airflight->id,
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
