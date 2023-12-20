<?php

namespace App\Http\Controllers\Api\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use App\Http\Controllers\Controller;
use App\Models\Hardware\HardwareDevice;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
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
 *
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
    public function getAircraftHistoryJson(Request $request)
    {
        $now = Carbon::now();
        $lastCheckTimestampMs = $request->get('_');

        ## Cantidad de archivos históricos.
        $historyLength = AirFlightRoute::HISTORY_LENGTH;

        ## Página actual del histórico para devolver
        $historyPage = $request->get('history') ?? 1;

        if ($historyPage > AirFlightRoute::HISTORY_LENGTH) {
            return response()->json([
                'now' => (Carbon::now())->getTimestamp(),
                'messages' => 0,
                'aircraft' => [],
            ]);
        }

        ## Rango de segundos para cada histórico en 10 minutos.
        $historySeconds = (int) round(600 / $historyLength);

        $checkFrom = clone($now);
        $checkTo = null;

        if ($lastCheckTimestampMs) {
            try {
                $checkFrom = Carbon::createFromTimestampMsUTC($lastCheckTimestampMs);
            } catch (Exception $e) {
                return response()->json([
                    'now' => (Carbon::now())->getTimestamp(),
                    'messages' => 0,
                    'aircraft' => [],
                ]);
            }
        }

        $subSeconds = $historyPage * $historySeconds;
        $addSeconds = $historySeconds;


        if ($historyPage && $historyPage >= 1) {
            $checkFrom = $checkFrom->subSeconds($subSeconds);
            $checkTo = (clone($checkFrom))->addSeconds($addSeconds);
        }

        $aircrafts = AirFlightAirPlane::getRecentsAircrafts($checkFrom, $checkTo);

        return response()->json([
            'now' => (Carbon::now())->getTimestamp(),
            'messages' => $aircrafts->sum('messages'),
            'aircraft' => $aircrafts->toArray(),
        ]);
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
            'history' => AirFlightRoute::HISTORY_LENGTH,
            'lat' => 36.7381,
            'lon' => -6.4301,
            'refresh' => 5000,
            'version' => 'api raupulus v1'
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
        $checkFrom = (clone($now))->subMinutes(10);
        $lastCheckTimestampMs = $request->get('_');

        if ($lastCheckTimestampMs) {
            try {
                $checkFrom = Carbon::createFromTimestampMsUTC($lastCheckTimestampMs);
                $checkFrom = $checkFrom->subSeconds(AirFlightRoute::HISTORY_LENGTH);
            } catch (Exception $e) {
                return response()->json([
                    'now' => (Carbon::now())->getTimestamp(),
                    'messages' => 0,
                    'aircraft' => [],
                ]);
            }
        }

        $aircrafts = AirFlightAirPlane::getRecentsAircrafts($checkFrom);

        return response()->json([
            'now' => (Carbon::now())->getTimestamp(),
            'messages' => $aircrafts->sum('messages'),
            'aircraft' => $aircrafts->toArray(),
        ]);
    }

    public function addJson(Request $request) {
        $hardware_id = $request->get('hardware_device_id');
        $hardware = $hardware_id ? HardwareDevice::find($hardware_id) : null;
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

                    if ($hardware && $airflight && !$airflight->hardware_device_id) {
                        $airflight->hardware_device_id = $hardware->id;
                    }

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
                                'hardware_device_id' => $hardware ? $hardware->id : null,
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

                        ## Marco el momento de la última ruta en el avión.
                        if ($route && $airflight) {
                            $airflight->route_last_at = $route->seen_at;
                            $airflight->save();
                        }
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
