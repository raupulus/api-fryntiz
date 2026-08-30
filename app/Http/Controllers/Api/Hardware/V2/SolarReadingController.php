<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hardware\V2;

use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Hardware\V2\StoreSolarReadingRequest;
use App\Http\Resources\V2\Hardware\SolarReadingResource;
use App\Services\Hardware\HardwareService;
use Illuminate\Http\JsonResponse;

/**
 * Subida de lecturas de un controlador solar (D109).
 */
class SolarReadingController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(private HardwareService $service) {}

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
