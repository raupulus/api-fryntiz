<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SmartPlant\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\SmartPlant\V2\StoreRegisterRequest;
use App\Http\Resources\V2\SmartPlant\SmartPlantRegisterResource;
use App\Http\Resources\V2\SmartPlant\SmartPlantResource;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Services\SmartPlant\SmartPlantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lecturas de las plantas.
 *
 * Las lecturas cuelgan de su planta, y eso no es cosmético:
 * `smartplant_registers` **no tiene columna `user_id`** (N288), así que la
 * planta es el único sitio donde consta de quién es una lectura. Con `plant_id`
 * suelto en el cuerpo y validado sólo con `exists`, cualquiera con la ability
 * `smartplant:write` podía escribir en la planta de otro (H5).
 */
class SmartPlantRegisterController extends BaseApiController
{
    public function __construct(private readonly SmartPlantService $service) {}

    /**
     * Plantas del usuario autenticado.
     */
    public function plants(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'created_at'],
            sortable: ['name', 'created_at', 'start_at'],
            defaultSortColumn: 'name',
            defaultSortDescending: false,
        );

        $query = SmartPlantPlant::query()->where('user_id', $request->user()->id);

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            SmartPlantResource::class
        );
    }

    /**
     * Lecturas de una planta.
     */
    public function index(Request $request, int $plant): JsonResponse
    {
        $planta = SmartPlantPlant::query()->find($plant);

        // Mismo 404 si no existe que si es de otro.
        if (! $planta || $request->user()->cannot('view', $planta)) {
            return $this->notFoundResponse('Planta no encontrada');
        }

        $collectionQuery = new CollectionQuery(
            filterable: ['created_at'],
            sortable: ['created_at', 'id'],
            defaultSortColumn: 'created_at',
        );

        $query = SmartPlantRegister::query()->where('plant_id', $plant);

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            SmartPlantRegisterResource::class
        );
    }

    /**
     * Guarda una lectura en la planta indicada por la URL.
     */
    public function store(StoreRegisterRequest $request, int $plant): JsonResponse
    {
        $register = $this->service->storeRegister($request->validated());

        return $this->createdResponse(
            new SmartPlantRegisterResource($register),
            'Registro de planta almacenado'
        );
    }
}
