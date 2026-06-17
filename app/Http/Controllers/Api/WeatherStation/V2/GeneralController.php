<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador general de estación meteorológica para API V2.
 */
class GeneralController extends BaseApiController
{
    public function __construct(private WeatherStationService $service) {}

    /**
     * Devuelve el resumen meteorológico actual.
     */
    public function resume(): JsonResponse
    {
        $data = $this->service->getResume();

        return $this->successResponse($data, 'Resumen meteorologico obtenido correctamente');
    }
}
