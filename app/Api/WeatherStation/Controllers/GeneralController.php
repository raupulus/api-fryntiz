<?php

namespace App\Api\WeatherStation\Controllers;

use function response;

class GeneralController
{
    /**
     * Devuelve a modo de resumen los datos básicos para el tiempo en la menor
     * cantidad de consultas posibles.
     */
    public function resume()
    {
        $data = ['hola' => true];

        return response()->json($data);
    }
}
