<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
})->name('home');

## Pantalla de bienvenida, la mantengo para ir a la docu oficial
Route::get('/welcome', function () {
    return view('welcome');
});

## Documentación
Route::middleware(['auth:sanctum', 'verified'])->get('/docs', function () {
    return view('documentation');
})->name('documentation');

############################################################
##                      Dashboard                         ##
############################################################
Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');
