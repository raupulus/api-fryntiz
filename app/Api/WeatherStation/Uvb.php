<?php

namespace App;

class Uvb extends BaseWheaterStation
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    protected $table = 'meteorology_uvb';
}
