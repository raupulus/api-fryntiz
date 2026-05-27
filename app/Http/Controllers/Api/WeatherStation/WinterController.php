<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\Wind;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

use function response;

/**
 * Class WinterController
 */
class WinterController extends BaseWheaterStationController
{
    protected $model = '\App\Models\WeatherStation\Wind';

    protected static function getModel(): string
    {
        return Wind::class;
    }

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param  $request
     * @return mixed
     */
    public function addValidate($data)
    {
        return Validator::make($data, [
            'hardware_device_id' => 'required|nullable',
            'speed' => 'required|numeric',
            'average' => 'required|numeric',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'created_at' => 'date_format:Y-m-d H:i:s',
        ])->validate();
    }

    /**
     * Devuelve todos los elementos del modelo.
     *
     * @return JsonResponse
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
