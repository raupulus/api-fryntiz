<?php

namespace App;

class Pressure extends BaseWheaterStation
{
    protected $table = 'meteorology_pressure';

    protected $fillable = [
        'value'
    ];
}
