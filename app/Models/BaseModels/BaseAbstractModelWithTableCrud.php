<?php

namespace App\Models\BaseModels;

use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Str;
use Validator;
use function array_key_exists;
use function array_keys;
use function auth;
use function preg_replace;
use function trim;

/**
 * Class BaseAbstractModelWithTableCrud
 * Modelo mínimo con funciones comunes a todos los modelos y además plantea los
 * métodos para implementar cruds dinámico en tablas.
 *
 * @package App
 */
abstract class BaseAbstractModelWithTableCrud extends BaseModel
{
    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    abstract protected static function getPolicy(): string|null;

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array Ejemplo: ['id' => 'ID','name' => 'Nombre';
     */
    abstract public static function getTableHeads(): array;

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][] ['name' => ['type' => 'text','wrapper' => 'span','class' => 'text-weight-bold']]
     */
    abstract public static function getTableCellsInfo(): array;

    /**
     * Devuelve las rutas de acciones para la columna de botones.
     * El botón especial "Eliminar" con clave "delete" será dinámico.
     */
    abstract public static function getTableActionsInfo(): Collection;

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     *
     * @return array
     */
    abstract public static function getFieldsValidation(): array;


    /**
     * Devuelve una cadena con los datos de acción para la tabla en formato
     * JSON.
     *
     * @return string Cadena con formato JSON.
     */
    public static function getTableActionsInfoJson(): string
    {
        return self::getModel()::getTableActionsInfo()->toJson(JSON_UNESCAPED_UNICODE);
    }

    /**
     * Devuelve un array con todos los atributos fillables del modelo
     *
     * @return array
     */
    public static function getAttributesFillable(): array
    {
        return (new (self::getModel())())->getFillable() ?? [];
    }

    /**
     * Prepara un valor para ser guardado en la base de datos.
     * Limpia los espacios en blanco de los atributos.
     * TODO → Previene injection de SQL.
     *
     * @param string|int|float|null $value
     * @param string                $attribute
     * @param string|null           $action
     *
     * @return string|null
     */
    public static function prepareValue(string|int|float|null $value,
                                        string $attribute,
                                        string|null $action): string|null
    {
        $value = trim($value);

        if ($attribute === 'slug') {
            $value = Str::slug($value);
        }

        return $value ?? null;
    }

    /**
     * Comprueba si puede editar un atributo concreto o en general.

     * @param int         $id
     * @param string|null $attribute Campo que se quiere editar.
     *
     * @return bool
     */
    protected static function checkCanEdit(int $id, string|null $attribute = null): bool {
        $model = self::getModel()::find($id);
        $policy = self::getModel()::getPolicy();
        $attributes = self::getAttributesFillable();

        if ($policy) {

            // TODO → Lo interesante es validar solo el atributo que se quiere editar
            // Para eso se planteaba "checkFieldValidation()"

            $policy = new $policy();
            return auth()->id() && auth()->user()->can('update', $model);
        }

        return true;
    }

    /**
     * Comprueba si puede eliminar un atributo concreto o en general.
     *
     * @param int $id
     *
     * @return bool
     */
    protected static function checkCanDelete(int $id): bool
    {

        $model = self::getModel()::find($id);
        $policy = self::getModel()::getPolicy();

        if ($policy) {
            $policy = new $policy();
            return auth()->id() && auth()->user()->can('update', $model);
        }

        return true;
    }


    /**
     * Comprueba si un atributo recibido cumple las reglas de validación.
     *
     * @param string $field Nombre del atributo.
     * @param string|null $value Nuevo valor del atributo.
     * @param int|null $id Id del elemento cuando se actualiza.
     *
     * @return array
     */
    protected static function checkFieldValidation(string $field, string|null $value, int|null $id): array
    {
        $validations = self::getModel()::getFieldsValidation();

        if (array_key_exists($field, $validations)) {
            $prepareRules = preg_replace('/\{id\}/', $id, $validations[$field]);

            $validator = Validator::make([$field => $value], [
                $field => $prepareRules,
            ]);

            //dd($validator->errors()->messages(), $value, $prepareRules, $field, $id);
            $errors = $validator->errors()->messages();

            return isset($errors[$field]) ? $errors[$field] : [];
        }

        return [
            ['No existe'],
        ];
    }

    /**
     * Devuelve los resultados para una página respetando dirección y campos
     * para ordenar.
     *
     * @param array  $columns Columnas a mostrar.
     * @param string $orderBy Columna por la que se realizará el orden.
     * @param string $orderDirection Ordenación ascendente o descendente.
     * @param string|null $search Cadena de búsqueda.
     *
     * @return mixed
     */
    public static function prepareQueryFiltered(array $columns,
                                                string $orderBy = 'created_at',
                                                string $orderDirection = 'DESC',
                                                string|null $search = ''): Builder
    {
        $query = self::select($columns);

        // TODO → ¿SECURIZAR? para evitar SQL Injection eliminando código
        $search = trim($search);

        if ($search) {
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    if ($column !== 'id') {
                        $q->orWhere($column, 'LIKE', '%' . $search . '%');
                    }
                }
            });
        }

        $query->orderBy($orderBy, $orderDirection);

        return $query;
    }

    /**
     * Devuelve los datos preparados para una tabla.
     *
     * @param int         $page           La página a devolver.
     * @param int         $size           Cantidad de elementos por página.
     * @param string|null $orderBy        Campo sobre el que se ordena.
     * @param string|null $orderDirection Dirección al ordenar (ASC|DESC)
     * @param string|null $search
     *
     * @return array
     */
    public static function getTableQuery(int         $page = 1,
                                         int         $size = 10,
                                         string|null $orderBy = 'created_at',
                                         string|null $orderDirection = 'DESC',
                                         string|null $search = ''): array
    {
        $tableHeads = self::getModel()::getTableHeads($page);
        $columns = array_keys($tableHeads);

        $query = self::prepareQueryFiltered($columns, $orderBy, $orderDirection, $search);
        $totalElements = $query->count();
        $tableRows = $query->offset(($page * $size) - $size)->limit($size)->get();

        $cellsInfo = self::getModel()::getTableCellsInfo();

        return [
            'heads' => $tableHeads,
            'rows' => $tableRows,
            'currentPage' => $page,
            'totalElements' => $totalElements,
            'size' => $size,
            'cellsInfo' => $cellsInfo,
        ];
    }
}
