<?php
/*
 * Archivo de rutas para la api para la pulsación de teclas y ratón con él
 * sufijo /keycounter/*
 */

use Illuminate\Support\Facades\Route;

######################################################
##                    Pública
######################################################
Route::get('/', 'App\Http\Controllers\KeyCounter\KeyCounterController@index')->name('keycounter.index');

Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:sanctum']
], function () {
    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/keyboard/add', 'App\Http\Controllers\Api\KeyCounter\KeyboardController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/keyboard/add-json', 'App\Http\Controllers\Api\KeyCounter\KeyboardController@addJson');

    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/mouse/add', 'App\Http\Controllers\Api\KeyCounter\MouseController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/mouse/add-json', 'App\Http\Controllers\Api\KeyCounter\MouseController@addJson');
});

