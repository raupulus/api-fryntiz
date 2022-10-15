<?php
/*
 * Archivo de rutas para la api de registros para plantas y sus
 * condiciones con él sufijo /smartplant/*
 */

use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\SmartPlant\SmartPlantController@index')->name('smartplant.index');
