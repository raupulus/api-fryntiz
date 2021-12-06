<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Auth;
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

## Documentación
Route::middleware(['auth:sanctum', 'verified'])->get('/docs', function () {
    return view('documentation');
})->name('documentation');

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['prefix' => '/languages'], function () {
    Route::group(['prefix' => '/ajax'], function () {
        Route::match(['get', 'post'], '/get/languages', [LanguageController::class, 'ajaxGetLanguages'])
            ->name('language.ajax.get.languages');
    });
});

Auth::routes();
