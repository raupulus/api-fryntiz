<?php

use App\Http\Controllers\Dashboard\CurriculumController;
use App\Http\Controllers\Dashboard\LanguageController;
use Illuminate\Support\Facades\Route;

############################################################
##                      Dashboard                         ##
############################################################
Route::group(['prefix' => '/', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', function () {
        return view('dashboard.index');
    })->name('dashboard.index');
});

############################################################
##                       Idiomas                          ##
############################################################
Route::group(['prefix' => '/language', 'middleware' => ['auth', 'verified']],
function () {
    Route::get('/index', [LanguageController::class, 'index'])
        ->name('dashboard.language.index');
    Route::get('/create', [LanguageController::class, 'create'])
        ->name('dashboard.language.create');
    Route::post('/store', [LanguageController::class, 'store'])
        ->name('dashboard.language.store');
    Route::get('/edit/{id}', [LanguageController::class, 'edit'])
        ->name('dashboard.language.edit');
    Route::post('/update/{id}', [LanguageController::class, 'update'])
        ->name('dashboard.language.update');
    Route::post('/destroy', [LanguageController::class, 'destroy'])
        ->name('dashboard.language.destroy');
});

############################################################
##                     Curriculum                         ##
############################################################
Route::group(['prefix' => '/cv', 'middleware' => ['auth', 'verified']],
    function () {
        Route::get('/index', [CurriculumController::class, 'index'])
            ->name('dashboard.cv.index');
        Route::get('/create', [CurriculumController::class, 'create'])
            ->name('dashboard.cv.create');
        Route::post('/store', [CurriculumController::class, 'store'])
            ->name('dashboard.cv.store');
        Route::get('/edit/{id}', [CurriculumController::class, 'edit'])
            ->name('dashboard.cv.edit');
        Route::post('/update/{id}', [CurriculumController::class, 'update'])
            ->name('dashboard.cv.update');
        Route::post('/destroy', [CurriculumController::class, 'destroy'])
            ->name('dashboard.cv.destroy');
    });
