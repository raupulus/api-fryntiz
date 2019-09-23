<?php

namespace App;

class Uv extends BaseWheaterStation
{
    protected $fillable = [
        'uv_raw',
        'risk_level',
        'created_at'
    ];

    protected $table = 'meteorology_uv';
}
