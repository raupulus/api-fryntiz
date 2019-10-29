<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use function response;

class UvIndexController extends BaseWheaterStationController
{
    protected $model = '\App\UvIndex';

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
}
