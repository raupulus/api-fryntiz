<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Requests\Api\KeyCounter\V1\StoreMouseRequest;
use App\Models\KeyCounter\Mouse;
use JsonHelper;

/**
 * Class MouseController
 *
 * @package App\Http\Controllers\Api\Keycounter
 */
class MouseController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Models\KeyCounter\Mouse';


    /**
     * Almacena un elemento en el modelo.
     *
     * @param \App\Http\Requests\Api\KeyCounter\V1\StoreMouseRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMouseRequest $request)
    {
        Mouse::create($request->validated());

        return JsonHelper::created(['request' => $request->validated()]);
    }
}
