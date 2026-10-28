<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\KeyCounter\KeyCounterStatsResource;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Estadísticas acumuladas de KeyCounter para el usuario autenticado.
 *
 * Agrega todos los dispositivos de hardware del usuario para proporcionar
 * métricas en conjunto: total histórico (lifetime), del día actual (today) y
 * de la última hora (last hour).
 */
class StatsController extends BaseApiController
{
    public function __construct(private readonly KeyCounterService $service) {}

    /**
     * Muestra las estadísticas agregadas de teclado y ratón del usuario autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return $this->forbiddenResponse('Usuario inactivo o no autorizado');
        }

        $stats = $this->service->userStats((int) $user->id);

        return $this->successResponse(new KeyCounterStatsResource($stats));
    }
}
