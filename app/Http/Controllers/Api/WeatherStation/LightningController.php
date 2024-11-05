<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Http\Requests\Api\WeatherStation\StoreLightningBatchRequest;
use App\Http\Requests\Api\WeatherStation\StoreLightningRequest;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Lightning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function auth;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function response;

/**
 * Class LightningController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class LightningController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Lightning';

    protected static function getModel(): string
    {
        return Lightning::class;
    }

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * TOFIX: Legacy, extraido a StoreLightningRequest. Borrar cuando termine de actualizar clientes!!!!!
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($data)
    {
        return Validator::make($data, [
            'hardware_device_id' => 'nullable',
            'distance' => 'required|numeric',
            'energy' => 'required|numeric',
            'noise_floor' => 'nullable|numeric',
            'created_at' => 'required'
            //'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Procesa el guardado de un elemento en la base de datos.
     *
     * @param StoreLightningRequest $request
     * @return JsonResponse
     */
    public function store(StoreLightningRequest $request): JsonResponse
    {
        return \JsonHelper::created([
            'message' => 'Guardado Correctamente',
            'fails' => Lightning::create($request->validated()) ? 0 : 1,
        ]);
    }

    public function storeBatch(StoreLightningBatchRequest $request): JsonResponse
    {
        $data = $request->validated();

        // TODO: Borrar cuando acabe de depurar!!!
        Log::info($data);

        $errors = 0;

        foreach ($data['lightnings'] as $lightning) {
            try {
                Lightning::create($lightning);
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return \JsonHelper::created([
            'message' => 'Recursos Creados: ' . count($data['lightnings']) - $errors,
            'fails' => $errors,
        ]);

    }

    /**
     * Recibe JSON con datos para guardar por lote.
     *
     * TOFIX: Legacy, extraido a storeBatch. Borrar cuando termine de actualizar clientes!!!!!
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function addJson(Request $request)
    {
        $deviceID = $request->get('hardware_device_id');

        $data = json_decode($request->get('data'));

        $fallidos = 0;

        ## Proceso cada dato recibido mediante JSON.
        foreach ($data as $d) {
            try {
                $model = new $this->model;

                ## Parseo la fecha
                $d->created_at = (new \DateTime($d->created_at))->format('Y-m-d H:i:s');

                ## Obtengo atributos y los válidos para excluir posible basura.
                $attributes = $this->addValidate(get_object_vars($d));

                $model->fill($attributes);

                if ($deviceID) {
                    $model->hardware_device_id = $deviceID;
                }

                $model->user_id = auth()->id();

                $model->save();
            } catch (Exception $e) {
                Log::error('Error insertando datos estación meteorológica');
                Log::error($e);
                $fallidos++;
            }
        }

        ## Respuesta cuando se ha guardado el modelo correctamente
        if ($fallidos == 0) {
            return response()->json('Guardado Correctamente', 201);
        } else if ($fallidos >= 1) {
            return response()->json('Fallidos: ' . $fallidos, 200);
        }

        return response()->json('No se ha guardado nada', 500);
    }
}
