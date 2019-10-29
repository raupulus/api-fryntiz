<?php

namespace App;

class Tvoc extends BaseWheaterStation
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    protected $table = 'meteorology_tvoc';
}
