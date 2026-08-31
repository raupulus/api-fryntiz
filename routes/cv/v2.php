<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Cv\V2\CurriculumController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 — Currículums
|--------------------------------------------------------------------------
|
| Antes eran diez rutas planas (`/cv/experience`, `/cv/skills`…) que daban por
| hecho que existe **un** currículum: devolvían siempre el del superadmin. El
| módulo tiene dieciocho tablas montadas precisamente para tener varios, uno por
| objetivo, así que cada sección cuelga ahora de su CV.
|
*/

Route::prefix('curricula')->group(function () {
    // Listado: sólo los públicos (B3).
    Route::get('/', [CurriculumController::class, 'index'])->name('api.v2.curricula.index');

    // Enlace privado. Va antes de /{slug} para que no se lo coma el parámetro.
    Route::get('/shared/{shareToken}', [CurriculumController::class, 'shared'])
        ->where('shareToken', '[A-Fa-f0-9]{64}')
        ->name('api.v2.curricula.shared');

    Route::get('/{slug}', [CurriculumController::class, 'show'])->name('api.v2.curricula.show');

    // Una sección suelta: experiences, educations, skills, projects,
    // repositories, services, collaborations, hobbies, jobs.
    Route::get('/{slug}/{section}', [CurriculumController::class, 'section'])
        ->where('section', 'experiences|educations|skills|projects|repositories|services|collaborations|hobbies|jobs')
        ->name('api.v2.curricula.section');
});
