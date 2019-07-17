<?php

namespace App\Http\Controllers;

use App\Humidity;
use Illuminate\Http\Request;
use function response;

class HumidityController extends Controller
{

    /**
     * Devuelve todos los elementos de humedad.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        return response()->json(Humidity::all());
    }
}
