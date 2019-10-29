<?php

namespace App;

class Uva extends BaseWheaterStation
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    protected $table = 'meteorology_uva';
}
