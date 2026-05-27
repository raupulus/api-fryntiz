<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

use function response;

/**
 * Class KeyCounterController
 */
abstract class KeyCounterController extends Controller
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model;

    /**
     * @var string Mensaje de error al agregar un nuevo dato.
     */
    protected $addError = '';

    /**
     * Devuelve todos los elementos del modelo.
     *
     * @return JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('value')
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($model);
    }
}
