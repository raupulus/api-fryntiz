<?php

use App\Http\Controllers\Keycounter\KeyCounterController;

class KeyboardController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Keycounter\Keyboard';
}
