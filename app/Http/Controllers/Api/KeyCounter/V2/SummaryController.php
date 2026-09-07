<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\ShowSummaryRequest;
use App\Services\KeyCounter\KeyCounterService;
use App\Support\Auth\TokenAbilities;
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
     *   ?date=today        el día de hoy (por defecto)
     *   ?date=month        el mes en curso
     *   ?date=2026-09-07   ese día
     *   ?date=2026-09      ese mes entero
     *   ?device_id=9       acota a un dispositivo; sin él, todos los del usuario
     */
    public function show(ShowSummaryRequest $request): JsonResponse
    {
        [$desde, $hasta] = $request->rango();

        // Sin `device_id` se devuelve lo de todos los dispositivos del usuario,
        // salvo que el token esté ligado a alguno: un token de cacharro suma lo
        // suyo y no el teclado de al lado.
        $devices = $request->dispositivo() !== null
            ? [$request->dispositivo()]
            : TokenAbilities::devicesReachableBy($request->user());

        $resumen = $this->service->summary(
            (int) $request->user()->id,
            $desde,
            $hasta,
            $devices
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
