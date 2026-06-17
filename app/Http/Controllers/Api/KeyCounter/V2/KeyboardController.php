<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\KeyCounter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\KeyCounter\V2\StoreKeyboardRequest;
use App\Http\Resources\V2\KeyCounter\KeyboardResource;
use App\Services\KeyCounter\KeyCounterService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de registro de teclado para API V2.
 */
class KeyboardController extends BaseApiController
{
    public function __construct(private KeyCounterService $service) {}

    /**
     * Almacena un registro de pulsaciones de teclado.
     */
    public function store(StoreKeyboardRequest $request): JsonResponse
    {
        $keyboard = $this->service->storeKeyboard($request->validated());

        return $this->createdResponse(
            new KeyboardResource($keyboard),
            'Registro de teclado almacenado'
        );
    }
}
