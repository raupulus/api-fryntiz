<?php

namespace App;

class Winter extends BaseWheaterStation
{
    protected $fillable = [
        'speed',
        'average',
        'min',
        'max',
        'created_at'
    ];

    protected $table = 'meteorology_winter';
}
