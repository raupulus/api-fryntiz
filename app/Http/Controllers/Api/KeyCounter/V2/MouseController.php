<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\StoreMouseRequest;
use App\Http\Resources\V2\KeyCounter\MouseResource;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de registro de ratón para API V2.
 */
class MouseController extends BaseApiController
{
    public function __construct(private KeyCounterService $service) {}

    /**
     * Almacena un registro de clicks de ratón.
     */
    public function store(StoreMouseRequest $request): JsonResponse
    {
        $mouse = $this->service->storeMouse($request->validated());

        return $this->createdResponse(
            new MouseResource($mouse),
            'Registro de raton almacenado'
        );
    }
}
