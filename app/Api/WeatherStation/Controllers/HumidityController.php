<?php

namespace App\Http\Controllers;

use App\Humidity;
use function array_fill_keys;
use function array_keys;
use function array_merge;
use Illuminate\Http\Request;
use function response;

class HumidityController extends BaseWheaterStationController
{
    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        return response()->json(Humidity::all());
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

        $humidity = Humidity::whereNotNull('value');

        ## Filtra por fecha mínima (desde).
        if ($filter['date_min']) {
            $humidity->where('created_at', '>=', $filter['date_min']);
        }

        ## Filtra por fecha máxima (hasta).
        if ($filter['date_max']) {
            $humidity->where('created_at', '<=', $filter['date_max']);
        }

        ## Filtra por valor mínimo (desde).
        if ($filter['value_min']) {
            $humidity->where('created_at', '>=', $filter['value_min']);
        }

        ## Filtra por valor máximo (hasta).
        if ($filter['value_max']) {
            $humidity->where('created_at', '<=', $filter['value_max']);
        }

        ## Filtra por una fecha concreta
        // TODO → modificar para filtrar solo por año-dia
        if ($filter['date']) {
            $humidity->where('created_at', '=', $filter['date']);
        }

        return response()->json($humidity->get());
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
        $value = $requestValidate['value'];

        $newEntry = new Humidity([
            'value' => $value
        ]);

        if ($newEntry->save()) {
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
            'value_min' => 'nullable|digits_between:10,4',
            'value_max' => 'nullable|digits_between:10,4',
            'date' => 'nullable|date',
        ];

        $validate = $request->validate($rules);

        ## Saneo los valores que no existan para convertirlos en nulos.
        $valuesNull = array_fill_keys(array_keys($rules), null);
        return array_merge($valuesNull, $validate);
    }
}
