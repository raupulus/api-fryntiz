<?php

namespace App\Keycounter;

use App\MinModel;
use function array_key_exists;

class KeyCounter extends MinModel
{
    protected $fillable = [
        'start_at',
        'end_at',
        'pulsations',
        'pulsations_special_keys',
        'pulsation_average',
        'score',
        'weekday',
        'device_id',
        'device_name',
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
        $query->where('start_at', '!=', null)
            ->where('end_at', '!=', null)
            ->where('pulsations', '!=', null)
            ->where('pulsations_special_keys', '!=', null)
            ->where('pulsation_average', '!=', null)
            ->where('score', '!=', null)
            ->where('weekday', '!=', null)
            ->where('created_at', '!=', null)
            ->sortByDesc('created_at');
        return $query;
    }
}
