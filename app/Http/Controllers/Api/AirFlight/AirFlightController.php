<?php

namespace App\Http\Controllers\Api\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function auth;
use function GuzzleHttp\json_decode;
use function in_array;
use function public_path;
use function response;
use Illuminate\Support\Facades\Log;

/**
 * Class AirFlightController
 *
 * @package App\Http\Controllers\AirFlight
 */
class AirFlightController extends Controller
{
    /**
     * Devuelve el historial para la página del historial solicitada.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAircraftHistory(Request $request)
    {
        return $this->getAircraftjson($request);
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
            'history' => 120,
            'lat' => 36.7381,
            'lon' => -6.4301,
            'refresh' => 5000,
            'version' => 'api fryntiz v1'
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
        $checkTo = null;

        if ($lastCheckTimestampMs) {
            try {
                $checkFrom = Carbon::createFromTimestampMsUTC($lastCheckTimestampMs);
            } catch (Exception $e) {
                $checkFrom = (clone($now))->subMinutes(10);
            }
        } else {
            $checkFrom = (clone($now))->subMinutes(10);
        }

        if ($historyPage) {
            if (! $checkFrom) {
                $checkFrom = $checkFrom ?? (clone($now));
            }

            if ($historyPage > 0) {
                $checkFrom = $checkFrom->subSeconds($historyPage * 5);
                $checkTo = (clone($checkFrom))->subSeconds(($historyPage * 5) - 5);
            } else {
                $checkFrom = $checkFrom->subMinutes(10);
            }
        }

        ## Para el retraso al subir datos que serían descartados en seen_at
        $checkFrom = $checkFrom->subSeconds($historyPage ? 5 : 60);

        $aircrafts = AirFlightAirPlane::select([
                'airflight_airplanes.icao as hex',
                'airflight_airplanes.category',
                'airflight_airplanes.seen_last_at as seen_at',
                'airflight_routes.seen_at as route_seen_at',
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
            ]);

        $subq = AirFlightRoute::select([
            '*',
            DB::raw('max(seen_at) as max_seen_at')
        ])
            ->where('seen_at', '>=', $checkFrom)
            ->groupBy('id')
            //->having('seen_at', '=', 'max_seen_at)')
            ->orderByDesc('seen_at')
            ->limit(1);

        $aircrafts->leftJoinSub($subq, 'airflight_routes', function ($join) use ($checkFrom, $checkTo, $historyPage) {
                $join->on('airflight_routes.airplane_id', '=', 'airflight_airplanes.id');

                if ($historyPage) {
                    $join->where('airflight_routes.seen_at', '>=', $checkFrom);

                    if ($checkTo) {
                        $join->where('airflight_routes.seen_at', '<=', $checkTo);
                    }
                }
            })
        ;

        ## Para el historial tomo rangos horarios, aquí establezco el fin.
        if ($historyPage) {
            $aircrafts->where('airflight_airplanes.seen_last_at', '>=', (clone($now))->subMinutes(10));

            $aircrafts->where(function ($q) use($checkFrom) {
                return $q->whereNull('airflight_routes.seen_at')
                    ->orWhere('airflight_routes.seen_at', '>=', $checkFrom);
            });

            if ($checkTo) {
                $aircrafts->where(function ($q) use($checkTo) {
                    return $q->whereNull('airflight_routes.seen_at')
                        ->orWhere('airflight_routes.seen_at', '<=', $checkTo);
                });
            }
        } else {
            $aircrafts->where('airflight_airplanes.seen_last_at', '>=', $checkFrom);
        }

        $aircrafts = $aircrafts
            ->orderByDesc('airflight_airplanes.seen_last_at')
            ->orderByDesc('airflight_routes.seen_at')
            ->get();

        $aircrafts->map(function ($ele) use ($now, $checkFrom, $historyPage) {
            try {
                if ($historyPage && ($ele->route_seen_at || $ele->seen_at)) {
                    $seenAt = Carbon::createFromFormat('Y-m-d H:i:s', $ele->route_seen_at ?? $ele->seen_at);

                    $ele->seen_at = $seenAt->getTimestamp();
                    $ele->seen = $seenAt->diffInSeconds($checkFrom);
                    $ele->seen_pos = $seenAt->diffInSeconds($checkFrom);
                } else {
                    $seenAt = Carbon::createFromFormat('Y-m-d H:i:s', $ele->route_seen_at ?? $ele->seen_at);

                    $ele->seen_at = $seenAt->getTimestamp();
                    $ele->seen = $seenAt->diffInSeconds($now);
                    $ele->seen_pos = $seenAt->diffInSeconds($now);
                }
            } catch (Exception $e) {

                $ele->seen_at = (clone($checkFrom))->subSeconds(600)->getTimestamp();
                $ele->seen = 600;
                $ele->seen_pos = 600;
            }

            $ele->rssi = $ele->rssi ? (float) $ele->rssi : (float) -100;
            $ele->lat = (float) $ele->lat != 0 ? (float) $ele->lat : null;
            $ele->lon = (float) $ele->lon != 0 ? (float) $ele->lon : null;
            $ele->category = $ele->category ?? null;
            $ele->messages = $ele->messages ?? 1;

            ## Altitud convertida de metros a ft
            $ele->altitude = (float) $ele->altitude != 0 ? (float) $ele->altitude * 3.28084 : null;

            ## Velocidad convertida de kt a m
            $ele->speed = (float) $ele->speed != 0 ? (float) $ele->speed / 0.54 : null;

            if (!$ele->lat) {
                unset($ele->lat);
            }

            if (!$ele->lon) {
                unset($ele->lon);
            }

            if (!$ele->altitude) {
                unset($ele->altitude);
            }

            if (!$ele->speed) {
                unset($ele->speed);
            }

            if (!$ele->vert_rate) {
                unset($ele->vert_rate);
            }

            if (!$ele->track) {
                unset($ele->track);
            }

            if (!$ele->squawk) {
                unset($ele->squawk);
            }

            if (!$ele->flight) {
                unset($ele->flight);
            }

            if (!$ele->flight) {
                unset($ele->flight);
            }

            unset($ele->route_seen_at);

            return $ele;
        });

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
                        ]
                    );

                    ## Compruebo si no tiene el país o la bandera para buscarlos.
                    if (! $airflight->country || ! $airflight->flag) {
                        $hex = AirFlightAirPlane::searchHex($airflight->icao);

                        if ($hex) {
                            $airflight->flag = $hex['flag_image'];
                            $airflight->country = $hex['country'];
                        }
                    }

                    ## Añade o actualiza la última vez que se vió si es mayor a la actual
                    if (! $airflight->seen_last_at || ($airflight->seen_last_at < $d->seen_at)) {
                        $airflight->seen_last_at = $d->seen_at;
                    }

                    if (!$airflight->category || ($airflight->category == 'null')) {
                        $airflight->category = $d->category == 'null' ? null : $d->category;
                    }

                    if (!$airflight->user_id) {
                        $airflight->user_id = auth()->id();
                    }

                    ## Comprueba si no se marcó al verse por primera vez o hay fecha anterior.
                    if (! $airflight->seen_first_at || ($airflight->seen_first_at > $d->seen_at)) {
                        $airflight->seen_first_at = $d->seen_at;
                    }

                    $airflight->save();

                    ## Solo almaceno rutas cuando hay latitud y longitud.
                    if ($d->lat && ($d->lat != 0) && $d->lon && ($d->lon != 0)) {
                        $lastHour = (Carbon::now())->subHour()->format('Y-m-d H:i:s');
                        $lastSeenRoute = AirFlightRoute::where('seen_at', '<=', $lastHour )
                            ->where('airplane_id', $airflight->id)
                            ->where('lat', $d->lat)
                            ->where('lon', $d->lon)
                            ->orderByDesc('seen_at')
                            ->first();

                        $lastSeen = $lastSeenRoute ? $lastSeenRoute->seen_at : null;

                        if (! $d->flight || in_array($d->flight, ['null', 'none',  ''])) {
                            $d->flight = $lastSeenRoute && $lastSeenRoute->flight ? $lastSeenRoute->flight : null;
                        }

                        if (! $d->squawk || in_array($d->squawk, ['null', 'none',  ''])) {
                            $d->squawk = $lastSeenRoute && $lastSeenRoute->squawk ? $lastSeenRoute->squawk : null;
                        }

                        if (! $d->messages || in_array($d->messages, ['null', 'none',  ''])) {
                            $d->messages = $lastSeenRoute && $lastSeenRoute->messages ?  $lastSeenRoute->messages + 1 : null;
                        }

                        if (in_array($d->emergency, ['null', 'none', '', null])) {
                            $d->emergency = null;
                        }

                        $route = AirFlightRoute::updateOrCreate(
                            [
                                'airplane_id' => $airflight->id,
                                'seen_at' => $d->seen_at,
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
                                'messages' => $d->messages,
                                'rssi' => $d->rssi ?? -100,
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
