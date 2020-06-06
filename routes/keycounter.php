<?php
/*
 * Archivo de rutas para la api de estación meteorológica accesible desde el
 * sufijo /ws/*
 */

######################################################
##                    Pública
######################################################
Route::get('/', function () {
    return view();
});

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
    Route::post('/keyboard/add', 'KeyboardController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/keyboard/add-json', 'KeyboardController@addJson');

    ## Añadir nuevo registro de pulsaciones para el teclado.
    Route::post('/mouse/add', 'MouseController@add');

    ## Añadir nuevos registros de pulsaciones para el teclado  por lotes JSON
    Route::post('/mouse/add-json', 'MouseController@addJson');
});

