<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FileThumbnailController;
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

Route::group(['prefix' => '/file'], function () {

    Route::group(['prefix' => '/thumbnail'], function () {
        Route::get('/get/{module}/{id}/{slug?}', [FileThumbnailController::class, 'get'])
            ->name('file.thumbnail.get');
    });


    Route::get('/get/{module}/{id}/{slug?}', [FileController::class, 'get'])
        ->name('file.get');

    Route::post('/upload', [FileController::class, 'upload'])
        ->name('file.upload');

    Route::get('/download/{module}/{id}/{slug?}', [FileController::class, 'download'])
        ->name('file.download');

    Route::get('/resize/{module}/{id}/{width}/{slug?}', [FileController::class, 'resizeAndGet'])
        ->name('file.resize');

    Route::post('/delete/{id}', [FileController::class, 'delete'])
        ->name('file.delete');

});

Auth::routes();


/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
Route::fallback(function () {
    return abort(404); //default 404
});
