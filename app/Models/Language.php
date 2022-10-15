<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use function array_key_exists;
use function asset;

/**
 * App\Models\Language
 */
class Language extends Model
{
    protected $table = 'languages';

    /**
     * Devuelve la ruta hacia los iconos en tamaño 64x64 píxeles.
     *
     * @return string
     */
    public function getUrlIcon64Attribute()
    {
        return asset($this->icon64);
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'icon64' => 'Icono',
            'locale' => 'Locale',
            'iso_locale' => 'ISO Locale',
            'iso2' => 'ISO2',
            'iso3' => 'ISO3',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo()
    {
        return [
            'icon64' => [
                'type' => 'icon',
                'size' => '64x64',
            ],
            'iso2' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'iso3' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
        ];
    }

    /**
     * Devuelve los resultados para una página.
     *
     * @param number $size Tamaño de cada página
     * @param number $page Página a la que buscar.
     *
     * @return array
     */
    public static function getTableRowsByPage($size, $page, $columns,
                                              $orderBy, $orderDirection = 'ASC')
    {
        return self::select($columns)
            ->offset(($page * $size) - $size)
            ->limit($size)
            ->orderBy($orderBy, $orderDirection)
            ->get();
    }

    /**
     * Devuelve un array con todos los atributos para un modelo instanciado
     *
     * @return array
     */
    public function getAllAttributes()
    {
        $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        // $columns = \Schema::getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }
        return $attributes;
    }
}
