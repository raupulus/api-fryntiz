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
| `/{slug}` (la vista) va DESPUÉS de `/s/{shareToken}` y `/pdf`: son literales
| de un solo segmento y, si `/{slug}` se registrara antes, se comería «s» y
| «pdf» como si fueran un slug real y esas dos rutas nunca se alcanzarían.
|
*/

use App\Http\Controllers\Cv\CurriculumController;
use Illuminate\Support\Facades\Route;

Route::prefix('/')->group(function () {
    // Listado de currículums públicos: tarjetas para entrar a cada uno.
    Route::get('/', [CurriculumController::class, 'index'])->name('cv.index');

    // Enlace privado. Va antes de /{slug} para que no se lo coma el parámetro.
    Route::get('/s/{shareToken}', [CurriculumController::class, 'sharedPdf'])
        ->where('shareToken', '[A-Fa-f0-9]{64}')
        ->name('cv.shared.pdf');

    Route::get('/pdf', [CurriculumController::class, 'defaultPdf'])->name('cv.pdf.default');

    Route::get('/{slug}/pdf', [CurriculumController::class, 'pdf'])->name('cv.pdf');

    // Vista pública de un CV. Va la última: es el catch-all de un segmento.
    Route::get('/{slug}', [CurriculumController::class, 'show'])->name('cv.show');
});
