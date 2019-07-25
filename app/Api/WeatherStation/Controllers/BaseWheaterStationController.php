<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use function GuzzleHttp\json_decode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use function response;
use function utf8_decode;

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
        return response()->json($this->model::all());
    }

    /**
     * Devuelve un conjunto de datos filtrados.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
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
        $requestValidate = $this->addValidate($request);

        $model = new $this->model;
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
     */
    public function addJson(Request $request)
    {
        $data = json_decode($request->get('data'));

        $fallidos = 0;

        foreach ($data as $d) {
            $model = new $this->model;

            // Created_at problemas, desactivar el de laravel?
            // cuidado que en add() si es necesario
            $model->fill([
                'value' => $d->value,
                //'created_at' => $d->created_at
            ]);

            try {
                $model->save();
            } catch (Exception $e) {
                Log::error('Error insertando datos estación meteorológica');
                Log::error($e);
                $fallidos++;
            }
        }

        return response()->json('Fallidos: ' . $fallidos, 200);

        ## Respuesta cuando se ha guardado el modelo correctamente
        if (true) {
            // response bien

            // TODO → Crear sistema de respuestas habituales 200,201,404,419...

            return response()->json('Guardado Correctamente', 201);
        }

        // response mal
        return response()->json('No se ha guardado nada', 500);
    }

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($request)
    {
        return $request->validate([
            'value' => 'required|numeric'
        ]);
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
            'value_max' => 'nullable|numeric',
            'date' => 'nullable|date',
        ];

        $validate = $request->validate($rules);

        ## Saneo los valores que no existan para convertirlos en nulos.
        $valuesNull = array_fill_keys(array_keys($rules), null);
        return array_merge($valuesNull, $validate);
    }
}
