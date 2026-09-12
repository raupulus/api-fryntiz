<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Energy\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Energy\V2\StoreEnergyRequest;
use App\Http\Requests\Api\Energy\V2\StoreEnergyTelemetryRequest;
use App\Http\Resources\V2\Energy\EnergyMonitorResource;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerLoad;
use App\Services\Hardware\HardwareService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subida de lecturas del monitor de energía (D115).
 */
class EnergyMonitorController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Lecturas de energía, paginadas.
     *
     * Exige `energy:read`. Consumo y generación viven en dos tablas
     * distintas —comparten columnas por el trait `IsEnergyReading`, no la
     * tabla—, así que se elige con `?type=load|generator`; por defecto,
     * consumo.
     *
     * Sólo devuelve lecturas de dispositivos del usuario y, si el token está
     * ligado a dispositivos concretos (`device:{id}`), sólo las de ésos.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('role') || $request->boolean('unified')) {
            return app(EnergyReadingController::class)->index($request);
        }

        $type = $request->query('type', 'load');

        if (! in_array($type, ['load', 'generator'], true)) {
            return $this->errorResponse('El parámetro «type» sólo admite «load» o «generator».', 422);
        }

        return $type === 'generator'
            ? $this->page(HardwarePowerGenerator::query(), $request)
            : $this->page(HardwarePowerLoad::query(), $request);
    }

    /**
     * Acota la consulta a lo que alcanza quien pregunta y devuelve su página.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function page(Builder $query, Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'hardware_energy_id', 'date', 'read_at'],
            sortable: ['read_at', 'date', 'id'],
            defaultSortColumn: 'read_at',
            defaultSortDescending: true,
        );

        $query->whereHas('hardwareDevice', fn ($q) => $q->where('user_id', $request->user()->id));

        $declaredDevices = TokenAbilities::devicesReachableBy($request->user());

        if ($declaredDevices !== []) {
            $query->whereIn('hardware_device_id', $declaredDevices);
        }

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            EnergyMonitorResource::class
        );
    }

    /**
     * Almacena las lecturas de un monitor de energía.
     *
     * Soporta tanto el contrato universal `energy` (D115) como el contrato legacy `readings`.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('energy')) {
            $telemetryRequest = app(StoreEnergyTelemetryRequest::class);

            return app(EnergyReadingController::class)->store($telemetryRequest);
        }

        $storeRequest = app(StoreEnergyRequest::class);
        $data = $storeRequest->validated();

        ['readings' => $readings, 'warnings' => $warnings] = $this->service->storeEnergyData($data);

        $this->storeDeviceInfoIfPresent($request, $this->service, (int) $data['hardware_device_id']);

        // Si no se ha guardado nada es porque el dispositivo no tiene ningún
        // elemento activo en `hardware_energy`, o porque ninguna `pos` de la
        // petición casa con una `sensor_position`. Antes respondía 201 igual y
        // el dato se perdía sin avisar.
        if ($readings === []) {
            return $this->errorResponse(
                'Ninguna lectura se ha podido asignar: revisa que el dispositivo tenga elementos '.
                'activos dados de alta y que los canales coincidan con sus posiciones de sensor.',
                422,
                $warnings
            );
        }

        return $this->withWarnings(
            $this->createdResponse(
                EnergyMonitorResource::collection($readings),
                'Lecturas de energia almacenadas'
            ),
            $warnings
        );
    }
}
