<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Energy\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesEnergyTelemetry;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Energy\V2\StoreEnergyTelemetryRequest;
use App\Http\Resources\V2\Energy\EnergyReadingResource;
use App\Models\Hardware\HardwareEnergyReading;
use App\Services\Hardware\HardwareService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API V2 unificado para lecturas y telemetría de energía (D115, Fase 5).
 */
class EnergyReadingController extends BaseApiController
{
    use HandlesEnergyTelemetry;
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Consulta paginada de lecturas de energía unificadas.
     *
     * Requiere ability `energy:read`. Filtra por dispositivo, elemento o rango temporal.
     */
    public function index(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'hardware_energy_id', 'created_at'],
            sortable: ['created_at', 'id'],
            defaultSortColumn: 'created_at',
            defaultSortDescending: true,
        );

        $query = HardwareEnergyReading::query()
            ->with(['hardwareEnergy', 'hardwareDevice'])
            ->whereHas('hardwareDevice', fn ($q) => $q->where('user_id', $request->user()->id));

        $declaredDevices = TokenAbilities::devicesReachableBy($request->user());

        if ($declaredDevices !== []) {
            $query->whereIn('hardware_device_id', $declaredDevices);
        }

        // Filtro opcional por rol
        if ($request->has('role')) {
            $role = (string) $request->query('role');
            $query->whereHas('hardwareEnergy', fn ($q) => $q->where('role', $role));
        }

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            EnergyReadingResource::class
        );
    }

    /**
     * Ingesta universal de telemetría de energía.
     *
     * Requiere ability `energy:write`. Procesa subsistemas de generador, batería y consumos.
     */
    public function store(StoreEnergyTelemetryRequest $request): JsonResponse
    {
        $deviceId = (int) $request->input('hardware_device_id');
        $energy = (array) $request->validated('energy');

        $this->storeDeviceInfoIfPresent($request, $this->service, $deviceId);

        ['readings' => $readings, 'warnings' => $warnings] = $this->service->storeEnergyTelemetry($deviceId, $energy);

        if ($readings === []) {
            return $this->errorResponse(
                'No se pudo registrar ninguna lectura de energía.',
                422,
                $warnings
            );
        }

        return $this->withWarnings(
            $this->createdResponse(
                EnergyReadingResource::collection($readings),
                'Telemetría de energía almacenada correctamente.'
            ),
            $warnings
        );
    }
}
