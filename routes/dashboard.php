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
        ## Tipos de repositorios disponibles.
        Route::get('/repository-available-type/index',
            [CurriculumAvailableRepositoryTypeController::class, 'index'])
            ->name('dashboard.cv.repository_available_type.index');
        Route::get('/repository-available-type/create',
            [CurriculumAvailableRepositoryTypeController::class, 'create'])
            ->name('dashboard.cv.repository_available_type.create');
        Route::get('/repository-available-type/edit/{id}',
            [CurriculumAvailableRepositoryTypeController::class, 'edit'])
            ->name('dashboard.cv.repository_available_type.edit');
        Route::post('/repository-available-type/store', [CurriculumAvailableRepositoryTypeController::class, 'store'])
            ->name('dashboard.cv.repository_available_type.store');
        Route::post('/repository-available-type/update/{id}', [CurriculumAvailableRepositoryTypeController::class, 'update'])
            ->name('dashboard.cv.repository_available_type.update');
        Route::post('/repository-available-type/destroy', [CurriculumAvailableRepositoryTypeController::class, 'destroy'])
            ->name('dashboard.cv.repository_available_type.destroy');


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

        ## Repositorios
        Route::group(['prefix' => '/repository', 'middleware' => ['auth', 'verified']],
            function () {
                Route::get('/index', [CurriculumRepositoryController::class, 'index'])
                    ->name('dashboard.cv.repository.index');
                Route::get('/create', [CurriculumRepositoryController::class, 'create'])
                    ->name('dashboard.cv.repository.create');
                Route::post('/store', [CurriculumRepositoryController::class, 'store'])
                    ->name('dashboard.cv.repository.store');
                Route::get('/edit/{id}', [CurriculumRepositoryController::class, 'edit'])
                    ->name('dashboard.cv.repository.edit');
                Route::post('/update/{id}', [CurriculumRepositoryController::class, 'update'])
                    ->name('dashboard.cv.repository.update');
                Route::post('/destroy', [CurriculumRepositoryController::class, 'destroy'])
                    ->name('dashboard.cv.repository.destroy');
            }
        );
    }
);
