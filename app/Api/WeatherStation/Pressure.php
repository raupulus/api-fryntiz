<?php

namespace App;

class Pressure extends MinModel
{
    protected $table = 'meteorology_pressure';

    protected $fillable = [
        'value'
    ];
}
