<?php

namespace App\Http\Controllers;

use App\Pressure;
use App\Temperature;
use Illuminate\Http\Request;
use function response;

class TemperatureController extends Controller
{
    /**
     * Devuelve todos los elementos de Temperatura.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {
        return response()->json(Temperature::all());
    }
}
