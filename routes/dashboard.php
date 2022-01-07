<?php

use App\Http\Controllers\Dashboard\CurriculumController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAcademicComplementaryController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAcademicComplementaryOnlineController;
use App\Http\Controllers\Dashboard\Cv\CurriculumAvailableRepositoryTypeController;
use App\Http\Controllers\Dashboard\Cv\CurriculumRepositoryController;
use App\Http\Controllers\Dashboard\Cv\CurriculumServiceController;
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
            Route::get('/edit/{id}', [CurriculumRepositoryController::class, 'edit'])
                ->name('dashboard.cv.repository.edit');
            Route::post('/update/{id}', [CurriculumRepositoryController::class, 'update'])
                ->name('dashboard.cv.repository.update');
            Route::post('/destroy/{id}', [CurriculumRepositoryController::class, 'destroy'])
                ->name('dashboard.cv.repository.destroy');
        });

        ## Servicios
        Route::group(['prefix' => '/service'],  function () {
            Route::get('/index/{id}',  [CurriculumServiceController::class, 'index'])
                ->name('dashboard.cv.service.index');
            Route::get('/create', [CurriculumServiceController::class, 'create'])
                ->name('dashboard.cv.service.create');
            Route::post('/store/{cv_id}', [CurriculumServiceController::class, 'store'])
                ->name('dashboard.cv.service.store');
            Route::get('/edit/{id}', [CurriculumServiceController::class, 'edit'])
                ->name('dashboard.cv.service.edit');
            Route::post('/update/{id}', [CurriculumServiceController::class, 'update'])
                ->name('dashboard.cv.service.update');
            Route::post('/destroy/{id}', [CurriculumServiceController::class, 'destroy'])
                ->name('dashboard.cv.service.destroy');
        });

        ## Formación Académica Complementaria
        Route::group(['prefix' => '/academic-complementary'],  function () {
            Route::get('/index/{id}', [CurriculumAcademicComplementaryController::class, 'index'])
                ->name('dashboard.cv.academic_complementary.index');
            Route::get('/create', [CurriculumAcademicComplementaryController::class, 'create'])
                ->name('dashboard.cv.academic_complementary.create');
            Route::post('/store/{cv_id}', [CurriculumAcademicComplementaryController::class, 'store'])
                ->name('dashboard.cv.academic_complementary.store');
            Route::get('/edit/{id}', [CurriculumAcademicComplementaryController::class, 'edit'])
                ->name('dashboard.cv.academic_complementary.edit');
            Route::post('/update/{id}', [CurriculumAcademicComplementaryController::class, 'update'])
                ->name('dashboard.cv.academic_complementary.update');
            Route::post('/destroy/{id}', [CurriculumAcademicComplementaryController::class, 'destroy'])
                ->name('dashboard.cv.academic_complementary.destroy');
        });

        ## Formación Académica Online
        Route::group(['prefix' => '/academic-complementary-online'],  function () {
            Route::get('/index/{id}',  [CurriculumAcademicComplementaryOnlineController::class, 'index'])
                ->name('dashboard.cv.academic_complementary_online.index');
            Route::get('/create', [CurriculumAcademicComplementaryOnlineController::class, 'create'])
                ->name('dashboard.cv.academic_complementary_online.create');
            Route::post('/store/{cv_id}', [CurriculumAcademicComplementaryOnlineController::class, 'store'])
                ->name('dashboard.cv.academic_complementary_online.store');
            Route::get('/edit/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'edit'])
                ->name('dashboard.cv.academic_complementary_online.edit');
            Route::post('/update/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'update'])
                ->name('dashboard.cv.academic_complementary_online.update');
            Route::post('/destroy/{id}', [CurriculumAcademicComplementaryOnlineController::class, 'destroy'])
                ->name('dashboard.cv.academic_complementary_online.destroy');
        });
    }
);
