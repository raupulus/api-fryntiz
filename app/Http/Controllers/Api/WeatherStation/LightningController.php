<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Lightning;
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
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($data)
    {
        return Validator::make($data, [
            'hardware_device_id' => 'required|nullable', 'distance' => 'required|numeric',
            'energy' => 'required|numeric',
            'noise_floor' => 'nullable|numeric',
            'created_at' => 'required'
            //'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Recibe JSON con datos para guardar por lote.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function addJson(Request $request)
    {
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
