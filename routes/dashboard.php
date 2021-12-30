<?php

use App\Http\Controllers\Dashboard\CurriculumController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAvailableRepositoryTypeController;
use App\Http\Controllers\Dashboard\Cv\CurriculumRepositoryController;
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
        ## Curriculums
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

        ## Tipos de repositorios disponibles.
        Route::group(['prefix' => '/repository-available-type'],  function () {
            Route::get('/index',
                [CurriculumAvailableRepositoryTypeController::class, 'index'])
                ->name('dashboard.cv.repository_available_type.index');
            Route::get('/create',
                [CurriculumAvailableRepositoryTypeController::class, 'create'])
                ->name('dashboard.cv.repository_available_type.create');
            Route::get('/edit/{id}',
                [CurriculumAvailableRepositoryTypeController::class, 'edit'])
                ->name('dashboard.cv.repository_available_type.edit');
            Route::post('/store', [CurriculumAvailableRepositoryTypeController::class, 'store'])
                ->name('dashboard.cv.repository_available_type.store');
            Route::post('/update/{id}', [CurriculumAvailableRepositoryTypeController::class, 'update'])
                ->name('dashboard.cv.repository_available_type.update');
            Route::post('/destroy', [CurriculumAvailableRepositoryTypeController::class, 'destroy'])
                ->name('dashboard.cv.repository_available_type.destroy');
        });

        ## Repositorios
        Route::group(['prefix' => '/repository'],  function () {
            Route::get('/index/{id}',  [CurriculumRepositoryController::class, 'index'])
                ->name('dashboard.cv.repository.index');
            Route::get('/create', [CurriculumRepositoryController::class, 'create'])
                ->name('dashboard.cv.repository.create');
            Route::post('/store/{cv_id}', [CurriculumRepositoryController::class, 'store'])
                ->name('dashboard.cv.repository.store');
            Route::get('/edit/{cv_id}/{id}', [CurriculumRepositoryController::class, 'edit'])
                ->name('dashboard.cv.repository.edit');
            Route::post('/update/{cv_id}/{id}', [CurriculumRepositoryController::class, 'update'])
                ->name('dashboard.cv.repository.update');
            Route::post('/destroy', [CurriculumRepositoryController::class, 'destroy'])
                ->name('dashboard.cv.repository.destroy');
        });
    }
);
