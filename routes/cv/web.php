<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Currículums en web (prefijo /cv)
|--------------------------------------------------------------------------
|
| La URL de cada CV, en la forma que acordamos (B2): `/cv/{slug}` para el
| público y `/cv/s/{token}` para el compartido por enlace.
|
| La ruta anterior era `/cv/get/pdf/raupulus/default`, que servía un fichero
| estático que no cambiaba al editar el CV.
|
*/

use App\Http\Controllers\Cv\CurriculumController;
use Illuminate\Support\Facades\Route;

Route::prefix('/')->group(function () {
    // Enlace privado. Va antes de /{slug} para que no se lo coma el parámetro.
    Route::get('/s/{shareToken}', [CurriculumController::class, 'sharedPdf'])
        ->where('shareToken', '[A-Fa-f0-9]{64}')
        ->name('cv.shared.pdf');

    Route::get('/pdf', [CurriculumController::class, 'defaultPdf'])->name('cv.pdf.default');

    Route::get('/{slug}/pdf', [CurriculumController::class, 'pdf'])->name('cv.pdf');
});
