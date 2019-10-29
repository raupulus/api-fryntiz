<?php

namespace App;

class Eco2 extends BaseWheaterStation
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    protected $table = 'meteorology_eco2';
}
