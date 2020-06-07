<?php

use App\Http\Controllers\Keycounter\KeyCounterController;

class MouseController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Keycounter\Mouse';
}
