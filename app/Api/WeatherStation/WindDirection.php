<?php

namespace App;

class WindDirection extends BaseWheaterStation
{
    protected $fillable = [
        'resistance',
        'direction',
        'created_at'
    ];

    protected $table = 'meteorology_wind_direction';

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query::whereNotNull('direction')
            ->orderBy('created_at', 'DESC')
            ->get();
        return $query;
    }
}
