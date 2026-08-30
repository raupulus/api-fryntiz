<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\StoreMouseRequest;
use App\Http\Resources\V2\KeyCounter\MouseResource;
use App\Models\KeyCounter\Mouse;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sesiones de ratón de KeyCounter.
 *
 * El recurso es la sesión de trabajo, no el ratón.
 */
class MouseController extends BaseApiController
{
    public function __construct(private KeyCounterService $service) {}

    /**
     * Sesiones de mouse del usuario autenticado.
     *
     * No existía: el módulo sólo tenía escritura y los datos se veían por
     * Blade. Una API que sólo deja escribir obliga a que cualquier cliente que
     * quiera pintarlos se meta en la base de datos por su cuenta.
     */
    public function index(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'start_at', 'end_at', 'created_at'],
            sortable: ['start_at', 'end_at', 'created_at'],
            defaultSortColumn: 'start_at',
        );

        $query = Mouse::query()->where('user_id', $request->user()->id);

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            MouseResource::class
        );
    }

    /**
     * Almacena un registro de clicks de ratón.
     */
    public function store(StoreMouseRequest $request): JsonResponse
    {
        $mouse = $this->service->storeMouse($request->validated());

        return $this->createdResponse(
            new MouseResource($mouse),
            'Registro de raton almacenado'
        );
    }
}
