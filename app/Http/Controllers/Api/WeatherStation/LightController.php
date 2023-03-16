<?php

namespace App\Http\Controllers\Api\WeatherStation;

use Illuminate\Support\Facades\Validator;

/**
 * Class LightController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class LightController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Light';

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
            'hardware_device_id' => 'required|nullable',
            'lumens' => 'required|numeric',
            'lux' => 'required|numeric',
            'index' => 'required|numeric',
            'uva' => 'required|numeric',
            'uvb' => 'required|numeric',
            'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('lux')
            ->whereNotNull('lumens')
            ->whereNotNull('index')
            ->whereNotNull('uva')
            ->whereNotNull('uvb')
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($model);
    }
}
