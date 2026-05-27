<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Requests\Api\KeyCounter\V1\StoreKeyboardRequest;
use App\Models\KeyCounter\Keyboard;
use Illuminate\Http\JsonResponse;
use JsonHelper;

/**
 * Class KeyboardController
 */
class KeyboardController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Models\KeyCounter\Keyboard';

    /**
     * Almacena un elemento en el modelo.
     *
     *
     * @return JsonResponse
     */
    public function store(StoreKeyboardRequest $request)
    {
        Keyboard::create($request->validated());

        return JsonHelper::created(['request' => $request->validated()]);
    }
}
