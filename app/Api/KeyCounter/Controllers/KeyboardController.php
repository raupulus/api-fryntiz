<?php

namespace App\Http\Controllers\KeyCounter;

use App\Http\Controllers\Controller;
use App\Keycounter\Keyboard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function response;

class KeyboardController extends Controller
{
    /**
     * @var string Mensaje de error al agregar un nuevo dato.
     */
    protected $addError = '';

    /**
     * Devuelve todos los elementos del modelo.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = Keyboard::whereNotNull('pulsations')
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->whereNotNull('pulsation_average')
            ->whereNotNull('score')
            ->whereNotNull('weekday')
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json($model);
    }

    /**
     * Añade una nueva entrada de la medición desde el sensor.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function add(Request $request)
    {
        $requestValidate = $this->addValidate($request->all());

        $model = new Keyboard();
        $model->fill($requestValidate);

        ## Respuesta cuando se ha guardado el modelo correctamente
        if ($model->save()) {
            // response bien

            // TODO → Crear sistema de respuestas habituales 200,201,404,419...

            return response()->json('Guardado Correctamente', 201);
        }

        // response mal
        return response()->json('No se ha guardado nada', 500);
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
                $model = new Keyboard();

                ## Parseo la fecha
                $d->created_at = (new \DateTime($d->created_at))->format('Y-m-d H:i:s');

                ## Obtengo atributos y los validos para excluir posible basura.
                $attributes = $this->addValidate(get_object_vars($d));

                $model->fill($attributes);

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
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'end_at' => 'required|date_format:Y-m-d H:i:s',
            'pulsations' => 'required|numeric',
            'pulsations_special_keys' => 'required|numeric',
            'pulsation_average' => 'required|numeric',
            'score' => 'required|numeric',
            'weekday' => 'required|integer|size:1',
            'created_at' => 'required|date_format:Y-m-d H:i:s',
        ])->validate();
    }
}
