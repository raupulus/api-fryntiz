<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Requests\Api\KeyCounter\V1\StoreKeyboardRequest;
use App\Models\KeyCounter\Keyboard;
use JsonHelper;

/**
 * Class KeyboardController
 *
 * @package App\Http\Controllers\KeyCounter
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
     * @param \App\Http\Requests\Api\KeyCounter\V1\StoreKeyboardRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreKeyboardRequest $request)
    {
        Keyboard::create($request->validated());

        return JsonHelper::created(['request' => $request->validated()]);
    }
}
