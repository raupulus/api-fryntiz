<?php

namespace App;

class Temperature extends MinModel
{
    protected $table = 'meteorology_temperature';

    protected $fillable = [
        'value'
    ];
}
