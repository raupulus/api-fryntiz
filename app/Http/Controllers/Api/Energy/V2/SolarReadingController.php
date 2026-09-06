<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Energy\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Energy\V2\StoreSolarReadingRequest;
use App\Http\Resources\V2\Energy\SolarReadingResource;
use App\Models\Hardware\HardwarePowerGeneratorSolar;
use App\Services\Hardware\HardwareService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subida de lecturas de un controlador solar (D109).
 */
class SolarReadingController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

    /**
     * Lecturas del controlador solar, paginadas.
     *
     * Exige `energy:read`. Sólo devuelve lecturas de dispositivos del
     * usuario y, si el token está ligado a dispositivos concretos
     * (`device:{id}`), sólo las de ésos: un token de cacharro no lee el resto
     * del parque de su dueño.
     */
    public function index(Request $request): JsonResponse
    {
        $collectionQuery = new CollectionQuery(
            filterable: ['hardware_device_id', 'hardware_energy_id', 'date', 'read_at'],
            sortable: ['read_at', 'date', 'id'],
            defaultSortColumn: 'read_at',
            defaultSortDescending: true,
        );

        $query = HardwarePowerGeneratorSolar::query()
            ->whereHas('hardwareDevice', fn ($q) => $q->where('user_id', $request->user()->id));

        $declarados = TokenAbilities::devicesReachableBy($request->user());

        if ($declarados !== []) {
            $query->whereIn('hardware_device_id', $declarados);
        }

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            SolarReadingResource::class
        );
    }

    /**
     * Almacena una lectura del controlador solar.
     */
    public function store(StoreSolarReadingRequest $request): JsonResponse
    {
        $data = $request->validated();

        ['reading' => $reading, 'warnings' => $warnings] = $this->service->storeSolarReading($data);

        $this->storeDeviceInfoIfPresent($request, $this->service, (int) $data['hardware_device_id']);

        return $this->withWarnings(
            $this->createdResponse(
                new SolarReadingResource($reading),
                'Lectura del controlador solar almacenada'
            ),
            $warnings
        );
    }
}
