<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JsonHelper;
use function count;
use function in_array;
use function is_array;

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
        $conditions = $request->json('conditions');


        /*
        $conditions = [
            [
                'filter' => 'where',
                'column' => 'platform_id',
                'value' => 3.
           ]];
        */


        $data = $this::getModel()::getTableQuery($page, $size, $orderBy,
            $orderDirection, $search, $conditions);

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
        $id = (int) $request->get('id');
        $attribute = $request->get('attribute');
        $value = $this::getModel()::prepareValue($request->get('value'), $attribute, $action);

        $success = false;
        $can = false;
        $modelString = $this::getModel();
        $model = $modelString::find($id);
        $fillable = (new $modelString())->getFillable() ?? [];

        ## Comprueba si el campo pasa la validación u obtiene errores.
        $fieldErrors = $this::getModel()::checkFieldValidation($attribute, $value, $id);

        if (!$fieldErrors || (is_array($fieldErrors) && !count ($fieldErrors))) {

            switch ($action) {
                case 'update':

                    ## Comprueba si puede editar ese atributo.
                    $can = $this::getModel()::checkCanEdit($id, $attribute);

                    if ($can && $id && (!count($fillable) || in_array($attribute, $fillable))) {
                        if ($model && $value) {
                            $success = ($model->{$attribute} = $value) && $model->save();
                        } else if ($model && !$value){
                            $success = true;
                            $model->{$attribute} = null;
                            $model->save();
                        }
                    }
                    break;

                case 'delete':

                    ## Comprueba si puede editar ese atributo.
                    $can = $this::getModel()::checkCanDelete($id);

                    // Comprueba si se puede eliminar el registro.
                    //$success = $model && $model->delete(); ->safeDelete();
                    break;
                default:
                    break;
            }
        }

        return JsonHelper::success([
            'success' => $success,
            'errors' => $fieldErrors,
            'can' => $can,
            'action' => $action,
            'id' => $id,
            'value' => $value,
            'attribute' => $attribute,
            'fillable' => $fillable,
            'modelString' => $modelString,
            'request' => $request->all(),
        ]);
    }
}
