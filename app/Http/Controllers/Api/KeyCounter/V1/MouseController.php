<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Requests\Api\KeyCounter\V1\StoreMouseRequest;
use App\Models\KeyCounter\Mouse;
use Illuminate\Http\JsonResponse;
use JsonHelper;

/**
 * Class MouseController
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
     *
     * @return JsonResponse
     */
    public function store(StoreMouseRequest $request)
    {
        Mouse::create($request->validated());

        return JsonHelper::created(['request' => $request->validated()]);
    }
}
