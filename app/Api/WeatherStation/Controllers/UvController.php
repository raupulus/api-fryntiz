<?php

namespace App\Http\Controllers;

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
    public function addValidate($request)
    {
        return $request->validate([
            'uv_raw' => 'required|numeric',
            'risk_level' => 'nullable|string',
        ]);
    }
}
