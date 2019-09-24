<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use function response;

class UvController extends BaseWheaterStationController
{
    protected $model = '\App\Uv';

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($data)
    {
        $validator = Validator::make($data, [
            'uv_raw' => 'required|numeric',
            'risk_level' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json('Error al validar datos de entrada', 500);
        }

        return $validator->validate();
    }

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('uv_raw')
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json($model);
    }
}
