<?php

namespace App\Http\Controllers;

use App\Http\Interfaces\TableCrudControllerInterface;
use App\Models\Tag;
use Illuminate\Http\Request;
use JsonHelper;

/**
 * Class Controller
 *
 * @package App\Http\Controllers
 */
abstract class BaseWithTableCrudController extends Controller
{
    /**
     * Devuelve el nombre con el espacio de nombre para el modelo asociado.
     *
     * @return string
     */
    abstract protected static function getModel(): string;

    /**
     * Devuelve el nombre con el espacio de nombre para la policy asociada.
     *
     * @return string
     */
    protected function getPolicy(): string
    {
        return ($this::getModel())::getPolicy();
    }

    /**
     * Devuelve un array con todos los atributos fillables del modelo
     *
     * @return array
     */
    protected function getAttributesFillable(): array
    {
        return (self::getModel())::fillable ?? [];
    }

    /**
     * Comprueba si puede editar un atributo concreto o en general.
     *
     * @return void
     */
    protected function checkCanEdit($attribute = null) {
        $policy = $this::getPolicy();
        $attributes = 'TODO → Obtener atributos del modelo';


        // Todo comprobar si en policy hay un método para comprobar si puede editar el atributo
        // Tal vez crear un sistema de roles para los usuarios y usar slug de
        // clave de para comprobar si puede editar el atributo

    }











    /**
     * Devuelve todas las etiquetas preparadas para mostrarlas en una tabla.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxTableGetQuery(Request $request)
    {
        $page = $request->json('page');
        $size = $request->json('size');
        $orderBy = $request->json('orderBy');
        $orderDirection = $request->json('orderDirection');
        $search = $request->json('search');

        $data = Tag::getTableQuery($page, $size, $orderBy, $orderDirection, $search);

        return JsonHelper::success([
            'data' => $data,
        ]);
    }

    public function ajaxTableActions(Request $request)
    {

    }
}
