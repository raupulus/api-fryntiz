<?php

namespace App\Http\Controllers\Api\WeatherStation;

use Illuminate\Support\Facades\Validator;
use function response;

/**
 * Class AirQualityController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class AirQualityController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\AirQuality';

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
            'gas_resistance' => 'required|numeric',
            'air_quality' => 'required|numeric',
            'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('gas_resistance')
            ->whereNotNull('air_quality')
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($model);
    }
}
