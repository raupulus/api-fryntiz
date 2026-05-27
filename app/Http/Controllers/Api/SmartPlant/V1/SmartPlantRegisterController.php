<?php

namespace App\Http\Controllers\Api\SmartPlant\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SmartPlant\V1\StoreRegisterRequest;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Http\JsonResponse;
use JsonHelper;

use function response;

/**
 * Class SmartPlantController
 */
class SmartPlantRegisterController extends Controller
{
    /**
     * Devuelve todos los elementos del modelo.
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function store(StoreRegisterRequest $request)
    {
        $plant = SmartPlantPlant::find($request->plant_id);

        if (! $plant) {
            return JsonHelper::notFound();
        }

        SmartPlantRegister::create($request->validated());

        return JsonHelper::created();
    }
}
