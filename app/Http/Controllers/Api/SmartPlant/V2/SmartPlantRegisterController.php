<?php

namespace App\Http\Controllers\Api\SmartPlant\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\SmartPlant\V2\StoreRegisterRequest;
use App\Http\Resources\V2\SmartPlant\SmartPlantRegisterResource;
use App\Services\SmartPlant\SmartPlantService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de registro de planta inteligente para API V2.
 */
class SmartPlantRegisterController extends BaseApiController
{
    public function __construct(private SmartPlantService $service) {}

    /**
     * Almacena un registro de datos de planta.
     */
    public function store(StoreRegisterRequest $request): JsonResponse
    {
        $register = $this->service->storeRegister($request->validated());

        return $this->createdResponse(
            new SmartPlantRegisterResource($register),
            'Registro de planta almacenado'
        );
    }
}
