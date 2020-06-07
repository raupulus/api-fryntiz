<?php

namespace App\Http\Controllers\Keycounter;

use App\Keycounter\KeyCounter;

class KeyboardController extends KeyCounter
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Keycounter\Keyboard';
}
