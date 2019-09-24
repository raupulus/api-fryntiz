<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use function response;

class WinterController extends BaseWheaterStationController
{
    protected $model = '\App\Winter';

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
            'speed' => 'required|numeric',
            'average' => 'required|numeric',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('speed')
            ->whereNotNull('average')
            ->whereNotNull('min')
            ->whereNotNull('max')
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($model);
    }
}
