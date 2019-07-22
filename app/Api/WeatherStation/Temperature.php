<?php

namespace App;

class Temperature extends BaseWheaterStation
{
    protected $table = 'meteorology_temperature';

    protected $fillable = [
        'value'
    ];
}
