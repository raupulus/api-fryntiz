<?php
/*w
 * Archivo de rutas para la api para la pulsación de teclas y ratón con él
 * sufijo /keycounter/*
 */

use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\KeyCounter\KeyCounterController@index')->name('keycounter.index');
