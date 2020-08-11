<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;

class LightningController extends BaseWheaterStationController
{
    protected $model = '\App\Lighting';

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
            'distance' => 'required|numeric',
            'energy' => 'required|numeric',
            'noise_floor' => 'required|numeric',
            'created_at' => 'required'
            //'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }
}
