<?php

namespace App;

class Humidity extends MinModel
{
    protected $table = 'meteorology_humidity';

    protected $fillable = [
        'value'
    ];
}
