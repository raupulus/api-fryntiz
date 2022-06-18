<?php

namespace App\Http\Controllers;

use App\Http\Interfaces\TableCrudControllerInterface;
use App\Models\Tag;
use Illuminate\Http\Request;
use JsonHelper;
use function count;
use function in_array;

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
     * @return string|null
     */
    protected function getPolicy(): string|null
    {
        return $this::getModel()::getPolicy();
    }






    /**
     * Comprueba si puede editar un atributo concreto o en general.
     *
     * @param string|null $attribute
     *
     * @return bool
     */
    protected function checkCanEdit(string $attribute = null): bool {
        $model = $this::getModel();
        $policy = $this::getPolicy();
        $attributes = $model::getAttributesFillable();


        // Todo comprobar si en policy hay un método para comprobar si puede editar el atributo
        // Tal vez crear un sistema de roles para los usuarios y usar slug de
        // clave de para comprobar si puede editar el atributo


        // TOFIX → Por ahora devuelvo siempre true hasta completar esté método
        return true;
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

        $data = $this::getModel()::getTableQuery($page, $size, $orderBy, $orderDirection, $search);

        return JsonHelper::success([
            'data' => $data,
        ]);
    }


    /**
     * Procesa acciones de la tabla sobre atributos.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxTableActions(Request $request)
    {
        $action = $request->get('action');
        $id = $request->get('id');
        $value = $request->get('value');
        $attribute = $request->get('attribute');

        $success = false;
        $modelString = $this::getModel();
        $fillable = (new $modelString())->fillable ?? [];
        $can = $this::checkCanEdit($action);

        switch ($action) {
            case 'update':

                if ($can && $id && (!count($fillable) || in_array($attribute, $fillable))) {
                    $model = $modelString::find($id);

                    $success = $model && ($model->{$attribute} = $value) && $model->save();
                }
                break;
        }


        return JsonHelper::success([
            'success' => $success,
            'action' => $action,
            'id' => $id,
            'value' => $value,
            'attribute' => $attribute,
            'fillable' => $fillable,
            'modelString' => $modelString,
            'request' => $request->all(),
        ]);


        // TODO → Obtener datos, validando los fillable!!!
        // TODO → Plantear si usar políticas
        // TODO → Usar validación de request para update

    }
}
