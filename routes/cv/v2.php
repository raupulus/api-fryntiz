<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Cv\V2\CvController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes — CV
|--------------------------------------------------------------------------
*/

Route::prefix('cv')->group(function () {
    Route::get('/', [CvController::class, 'index'])->name('api.v2.cv.index');
    Route::get('/experience', [CvController::class, 'experience'])->name('api.v2.cv.experience');
    Route::get('/education', [CvController::class, 'education'])->name('api.v2.cv.education');
    Route::get('/skills', [CvController::class, 'skills'])->name('api.v2.cv.skills');
    Route::get('/projects', [CvController::class, 'projects'])->name('api.v2.cv.projects');
    Route::get('/repositories', [CvController::class, 'repositories'])->name('api.v2.cv.repositories');
    Route::get('/services', [CvController::class, 'services'])->name('api.v2.cv.services');
    Route::get('/collaborations', [CvController::class, 'collaborations'])->name('api.v2.cv.collaborations');
    Route::get('/hobbies', [CvController::class, 'hobbies'])->name('api.v2.cv.hobbies');
    Route::get('/jobs', [CvController::class, 'jobs'])->name('api.v2.cv.jobs');
});
