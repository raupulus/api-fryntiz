<?php

namespace App;

class BaseWheaterStation extends MinModel
{
    protected $fillable = [
        'value',
        'created_at'
    ];

    /**
     * Sobreescribo la actualización del updated_at para no hacerle nada.
     *
     * @param mixed $value
     *
     * @return \App\MinModel|void
     */
    public function setUpdatedAt($value)
    {
        //Do-nothing
    }
}
