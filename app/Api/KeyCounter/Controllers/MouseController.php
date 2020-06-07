<?php

namespace App\Http\Controllers\KeyCounter;

use App\Keycounter\KeyCounter;

class MouseController extends KeyCounter
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Keycounter\Mouse';
}
