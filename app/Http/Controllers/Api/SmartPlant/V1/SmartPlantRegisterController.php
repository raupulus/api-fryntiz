<?php

namespace App\Http\Controllers\Api\SmartPlant\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmartPlant\V1\StoreRegisterRequest;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\SmartPlant\SmartPlantPlant;
use JsonHelper;
use function response;

/**
 * Class SmartPlantController
 *
 * @package App\Http\Controllers\SmartPlant
 */
class SmartPlantRegisterController extends Controller
{
    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = SmartPlantRegister::whereNotNull('soil_humidity')
            ->orderBy('start_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->whereNull('deleted_at')
            ->get();
        return response()->json($model);
    }

    /**
     * Almacena un elemento en el modelo.
     *
     * @param \App\Http\Requests\Api\SmartPlant\V1\StoreRegisterRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRegisterRequest $request)
    {
        $plant = SmartPlantPlant::find($request->plant_id);

        if (!$plant) {
            return JsonHelper::notFound();
        }

        SmartPlantRegister::create($request->validated());

        return JsonHelper::created();
    }
}
