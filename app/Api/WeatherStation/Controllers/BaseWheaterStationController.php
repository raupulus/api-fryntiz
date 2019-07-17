<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class BaseWheaterStationController extends Controller
{
    /**
     * Devuelve todos los elementos del modelo.
     * @return \Illuminate\Http\JsonResponse
     */
    public function all()
    {

    }

    /**
     * Devuelve un conjunto de datos filtrados.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function find(Request $request)
    {

    }

    /**
     * Añade una nueva entrada de la medición desde el sensor.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function add(Request $request)
    {

    }

    /**
     * Reglas de validación a la hora de insertar datos.
     *
     * @param $request
     *
     * @return mixed
     */
    public function addValidate($request)
    {

    }

    /**
     * Reglas de validación para las peticiones de búsqueda.
     *
     * @param $request
     *
     * @return array
     */
    public function findValidate($request)
    {

    }
}
