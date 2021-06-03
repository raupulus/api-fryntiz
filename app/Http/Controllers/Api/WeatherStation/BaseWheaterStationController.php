<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use function auth;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function response;

/**
 * Class BaseWheaterStationController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
abstract class BaseWheaterStationController extends Controller
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model;

    /**
     * @var string Mensaje de error al agregar un nuevo dato.
     */
    protected $addError = '';

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('value')
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json($model);
    }

    /**
     * Devuelve un conjunto de datos filtrados.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function find(Request $request)
    {
        $filter = $this->findValidate($request);

        $model = $this->model::whereNotNull('value');

        ## Filtra por fecha mínima (desde).
        if ($filter['date_min']) {
            $model->where('created_at', '>=', $filter['date_min']);
        }

        ## Filtra por fecha máxima (hasta).
        if ($filter['date_max']) {
            $model->where('created_at', '<=', $filter['date_max']);
        }

        ## Filtra por valor mínimo (desde).
        if ($filter['value_min']) {
            $model->where('value', '>=', $filter['value_min']);
        }

        ## Filtra por valor máximo (hasta).
        if ($filter['value_max']) {
            $model->where('value', '<=', $filter['value_max']);
        }

        ## Filtra por el día según una fecha concreta
        if ($filter['date']) {
            $date = new Carbon($filter['date']);
            $date->hour = 0;
            $date->minute = 0;
            $date->second = 0;

            $dateMin = $date->format('Y-m-d H:i:s');

            $date->addDay(1);
            $dateMax = $date->format('Y-m-d H:i:s');

            $model->where('created_at', '>=', $dateMin);
            $model->where('created_at', '<=', $dateMax);
        }

        $model->orderBy('created_at', 'DESC');

        return response()->json($model->get());
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

        $model = new $this->model;
        $model->fill($requestValidate);

        $model->user_id = auth()->id();

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
                $model = new $this->model;

                ## Parseo la fecha
                $d->created_at = (new \DateTime($d->created_at))->format('Y-m-d H:i:s');

                ## Obtengo atributos y los validos para excluir posible basura.
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
            'value' => 'required|numeric',
            'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Reglas de validación para las peticiones de búsqueda.
     *
     * @param $request
     *
     * @return array
     */
    public function findValidate($request)
    {
        $rules = [
            'date_min' => 'nullable|date',
            'date_max' => 'nullable|date',
            'value_min' => 'nullable|numeric',
            'value_ max' => 'nullable|numeric',
            'date' => 'nullable|date',
        ];

        $validate = $request->validate($rules);

        ## Saneo los valores que no existan para convertirlos en nulos.
        $valuesNull = array_fill_keys(array_keys($rules), null);
        return array_merge($valuesNull, $validate);
    }
}
