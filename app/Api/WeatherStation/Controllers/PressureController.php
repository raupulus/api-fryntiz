<?php

namespace App\Http\Controllers;

use App\Pressure;
use Illuminate\Http\Request;
use function response;

class PressureController extends Controller
{
    /**
     * Devuelve todos los elementos de presión.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        return response()->json(Pressure::all());
    }
}
