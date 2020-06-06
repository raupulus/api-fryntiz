<?php
/*
 * Archivo de rutas para la api para la pulsación de teclas y ratón con él
 * sufijo /keycounter/*
 */

######################################################
##                    Pública
######################################################
Route::get('/', 'KeyCounter\KeyCounterController@index');

Route::get('/test', function () {
    return 'Ruta de prueba accesible desde' . url('test');
});

######################################################
##                    Privada
######################################################
Route::group([
    'prefix' => '/',
    'middleware' => ['auth:api']
], function () {
    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/keyboard/add', 'KeyCounter\KeyboardController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/keyboard/add-json', 'KeyCounter\KeyboardController@addJson');

    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/mouse/add', 'KeyCounter\MouseController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/mouse/add-json', 'KeyCounter\MouseController@addJson');
});

