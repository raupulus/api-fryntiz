<?php

namespace App\Http\Controllers\Api\KeyCounter\V1;

use App\Http\Controllers\Api\KeyCounter\Exception;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use function auth;
use function get_object_vars;
use function GuzzleHttp\json_decode;
use function is_array;
use function response;

/**
 * Class KeyCounterController
 *
 * @package App\Http\Controllers\Api\Keycounter
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        $model = $this->model::whereNotNull('value')
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json($model);
    }
}
