<?php

namespace App\Http\Controllers\Api\WeatherStation;

use Illuminate\Support\Facades\Validator;

/**
 * Class LightningController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class LightningController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Lightning';

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
