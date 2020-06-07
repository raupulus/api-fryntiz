<?php

namespace App\Keycounter;

use App\MinModel;
use function array_key_exists;

class Keyboard extends MinModel
{
    protected $table = 'keycounter_keyboard';

    protected $fillable = [
        'value',
        'created_at'
    ];

    /**
     * Sobreescribo la actualización del updated_at para no hacerle nada.
     *
     * @param mixed $value
     *
     * @return \App\MinModel|void
     */
    public function setUpdatedAt($value)
    {
        //Do-nothing
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

        foreach ($columns as $column)
        {
            if (!array_key_exists($column, $attributes))
            {
                $attributes[$column] = null;
            }
        }
        return $attributes;
    }

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query->where('created_at', '!=', null)
            ->sortByDesc('created_at');
        return $query;
    }
}
