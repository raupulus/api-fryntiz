<?php
/*
 * Archivo de rutas para la api de curriculum con sus
 * condiciones con él sufijo /cv/*
 */

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Cv\CurriculumController;

Route::group(['prefix' => '/'], function () {
    ## Devuelve un contenido a partir de su slug
    Route::get('/get/pdf/raupulus/default', [CurriculumController::class, 'getPdfDefault']);
});
