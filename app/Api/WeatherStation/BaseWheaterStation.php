<?php

namespace App;

class BaseWheaterStation extends MinModel
{
    protected $fillable = [
        'value'
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
