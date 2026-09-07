<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\ShowSummaryRequest;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;

/**
 * Resumen acumulado de KeyCounter.
 *
 * Existe por una razón concreta: un contador que se apaga o se reinicia pierde
 * lo que llevaba contado del día. Al arrancar pide aquí el acumulado del
 * periodo y sigue sumando desde donde estaba, en vez de empezar de cero y
 * enseñar un total falso hasta medianoche.
 */
class SummaryController extends BaseApiController
{
    public function __construct(private readonly KeyCounterService $service) {}

    /**
     * Acumulado de un periodo.
     *
     *   ?device_id=9       el cacharro que pregunta (obligatorio)
     *   ?date=today        el día de hoy (por defecto)
     *   ?date=month        el mes en curso
     *   ?date=2026-09-07   ese día
     *   ?date=2026-09      ese mes entero
     */
    public function show(ShowSummaryRequest $request): JsonResponse
    {
        [$desde, $hasta] = $request->rango();

        $resumen = $this->service->summary(
            (int) $request->user()->id,
            $request->dispositivo(),
            $desde,
            $hasta
        );

        return $this->successResponse([
            'period' => $request->periodo(),
            'from' => $desde->toISOString(),
            'to' => $hasta->toISOString(),
            'hardware_device_id' => $request->dispositivo(),
            ...$resumen,
        ]);
    }
}
