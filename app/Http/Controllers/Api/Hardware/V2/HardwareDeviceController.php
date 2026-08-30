<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Hardware\V2\StoreDeviceStatusRequest;
use App\Http\Resources\V2\Hardware\DeviceStatusResource;
use App\Http\Resources\V2\Hardware\HardwareDeviceResource;
use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dispositivos hardware.
 *
 * Tres cambios respecto a lo que había:
 *
 *  1. `GET /hardware/device/{id}` hacía `find($id)` sin filtrar por dueño, y la
 *     ruta no exigía ninguna ability. Iterando el id salía el inventario
 *     completo de todos los usuarios, **con número de serie** (auditoría A3).
 *  2. `GET /hardware/computers` era una ruta propia para lo que es un filtro:
 *     `GET /hardware/devices?type=laptop`.
 *  3. El estado del dispositivo se subía con `POST /hardware/device-status`.
 *     Es «el último estado conocido», o sea que se sobrescribe: el método
 *     correcto es `PUT /hardware/devices/{device}/status`, que además es
 *     idempotente — repetir la petición no crea nada.
 */
class HardwareDeviceController extends BaseApiController
{
    public function __construct(private readonly HardwareService $service) {}

    /**
     * Dispositivos del usuario autenticado.
     *
     *   ?type=laptop,raspberry
     */
    public function index(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['name', 'created_at', 'last_seen_at'],
            sortable: ['name', 'created_at', 'last_seen_at'],
            defaultSortColumn: 'name',
            defaultSortDescending: false,
        );

        $query = HardwareDevice::query()
            ->where('user_id', $request->user()->id)
            ->with('type');

        // El tipo vive en otra tabla, así que no puede ser un filtro genérico.
        if ($request->filled('type')) {
            $tipos = array_filter(array_map('trim', explode(',', (string) $request->query('type'))));
            $query->whereHas('type', fn ($q) => $q->whereIn('slug', $tipos));
        }

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            HardwareDeviceResource::class
        );
    }

    /**
     * Un dispositivo del usuario autenticado.
     */
    public function show(Request $request, int $device): JsonResponse
    {
        $model = $this->service->getDeviceInfo($device, (int) $request->user()->id);

        // Mismo 404 si no existe que si es de otro: no se confirma la
        // existencia de dispositivos ajenos.
        if (! $model || $request->user()->cannot('view', $model)) {
            return $this->notFoundResponse('Dispositivo no encontrado');
        }

        return $this->successResponse(new HardwareDeviceResource($model));
    }

    /**
     * Sustituye el último estado conocido del dispositivo.
     *
     * PUT y no POST: no se está creando un recurso nuevo cada vez, se está
     * sobrescribiendo el mismo. Repetir la petición deja el sistema igual.
     */
    public function updateStatus(StoreDeviceStatusRequest $request, int $device): JsonResponse
    {
        $data = $request->validated();

        $model = $this->service->updateDeviceStatus($device, $data);

        return $this->successResponse(
            new DeviceStatusResource($model),
            'Estado del dispositivo actualizado'
        );
    }
}
