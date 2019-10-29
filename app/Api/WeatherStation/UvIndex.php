<?php

namespace App;

class UvIndex extends BaseWheaterStation
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    protected $table = 'meteorology_uv_index';
}
