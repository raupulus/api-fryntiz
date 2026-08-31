<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\StoreKeyboardRequest;
use App\Http\Resources\V2\KeyCounter\KeyboardResource;
use App\Models\KeyCounter\Keyboard;
use App\Services\Hardware\HardwareService;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sesiones de teclado de KeyCounter.
 *
 * El recurso es la sesión de trabajo (`start_at`/`end_at`), no el teclado:
 * `POST /keycounter/keyboard` sonaba a que se creaba un teclado.
 */
class KeyboardController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(
        private KeyCounterService $service,
        private HardwareService $hardwareService,
    ) {}

    /**
     * Sesiones de keyboard del usuario autenticado.
     *
     * No existía: el módulo sólo tenía escritura y los datos se veían por
     * Blade. Una API que sólo deja escribir obliga a que cualquier cliente que
     * quiera pintarlos se meta en la base de datos por su cuenta.
     */
    public function index(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'start_at', 'end_at', 'created_at'],
            sortable: ['start_at', 'end_at', 'created_at', 'pulsations'],
            defaultSortColumn: 'start_at',
        );

        $query = Keyboard::query()->where('user_id', $request->user()->id);

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            KeyboardResource::class
        );
    }

    /**
     * Almacena un registro de pulsaciones de teclado.
     */
    public function store(StoreKeyboardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $keyboard = $this->service->storeKeyboard($data);

        $this->storeDeviceInfoIfPresent($request, $this->hardwareService, (int) $data['hardware_device_id']);

        return $this->createdResponse(
            new KeyboardResource($keyboard),
            'Registro de teclado almacenado'
        );
    }
}
