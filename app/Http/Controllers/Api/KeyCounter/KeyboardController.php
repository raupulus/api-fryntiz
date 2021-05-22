<?php

namespace App\Http\Controllers\Api\KeyCounter;

/**
 * Class KeyboardController
 *
 * @package App\Http\Controllers\KeyCounter
 */
class KeyboardController extends KeyCounterController
{
    /**
     * @var string Ruta y modelo sobre el que se trabajará.
     */
    protected $model = '\App\Models\KeyCounter\Keyboard';
}
