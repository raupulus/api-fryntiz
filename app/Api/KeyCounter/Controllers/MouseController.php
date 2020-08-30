<?php

namespace App\Http\Controllers\Keycounter;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function is_array;
use function response;

class MouseController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Keycounter\Mouse';


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

                ## Obtengo atributos y los validos para excluir posible basura.
                $attributes = $this->addValidate(get_object_vars($d));

                if (is_array($attributes)) {
                    $model->fill($attributes);

                    ## Calculo la duración en segundos de la racha.
                    $start = new Carbon($model->start_at);
                    $end = new Carbon($model->end_at);
                    $duration = $start->diffInSeconds($end);

                    ## Almaceno la duración en segundos de la racha.
                    $model->duration = $duration;

                    $model->save();
                }
            } catch (Exception $e) {
                Log::error('Error insertando datos en contador de pulsaciones');
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
            'start_at' => 'required',
            'end_at' => 'required',
            'clicks_left' => 'required|numeric',
            'clicks_right' => 'required|numeric',
            'clicks_middle' => 'required|numeric',
            'total_clicks' => 'required|numeric',
            'clicks_average' => 'required|numeric',
            'device_id' => 'required|numeric',
            'device_name' => 'required',
            'weekday' => 'required|numeric',
            //'created_at' => 'nullable|date_format:Y-m-d H:i:s',
            'created_at' => 'nullable',
        ])->validate();
    }
}
