<?php
/*w
 * Archivo de rutas para la api para la pulsación de teclas y ratón con él
 * sufijo /keycounter/*
 */

use App\Http\Controllers\KeyCounter\KeyCounterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [KeyCounterController::class, 'index'])->name('keycounter.index');
