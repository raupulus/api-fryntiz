<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\EditorJsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileThumbnailController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Web\Newsletter\NewsletterPageController;
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

Route::get('/about', function () {
    return redirect()->route('home');
})->name('about');

/*
|--------------------------------------------------------------------------
| Documentación de la API (Scribe)
|--------------------------------------------------------------------------
|
| Las registra la aplicación, no Scribe (`laravel.add_routes => false`).
|
| Scribe es una dependencia de desarrollo y el servidor instala con
| `composer install --no-dev`: su ServiceProvider no llega a producción, así que
| la ruta que registraba él se quedaba en 404 justo donde hace falta. Lo que sí
| viaja en el repositorio es el resultado ya generado —la vista
| `resources/views/scribe/index.blade.php`, los assets de `public/vendor/scribe`
| y los ficheros de `storage/app/scribe`—, que es HTML y no necesita el paquete
| para servirse.
|
| La documentación se genera en local con `php artisan scribe:generate` y se
| commitea. Ver `docs/info/scribe.md`.
|
| Detrás de 'auth': la documentación de la API es privada.
|
*/
// Sólo 'auth': `withRouting(web: ...)` ya aplica el grupo 'web' a este fichero
// entero, y repetirlo aquí ejecutaba `StartSession` dos veces por petición, lo
// que dejaba la sesión inservible y devolvía al login a quien ya había entrado.
Route::middleware('auth')->group(function () {
    // La vista viaja en el repositorio, así que aquí no hay nada que comprobar.
    // Los dos ficheros de abajo sí viven en `storage/` y pueden no estar.
    Route::get('/docs', fn () => view('scribe.index'))->name('scribe');

    Route::get('/docs.openapi', function () {
        $path = storage_path('app/scribe/openapi.yaml');
        abort_unless(is_file($path), 404, 'La especificación OpenAPI no está generada.');

        return response()->file($path, ['Content-Type' => 'application/yaml']);
    })->name('scribe.openapi');

    Route::get('/docs.postman', function () {
        $path = storage_path('app/scribe/collection.json');
        abort_unless(is_file($path), 404, 'La colección de Postman no está generada.');

        return response()->file($path, ['Content-Type' => 'application/json']);
    })->name('scribe.postman');
});

/*
|--------------------------------------------------------------------------
| Editor.js
|--------------------------------------------------------------------------
|
| Lo que el editor del panel necesita del servidor. Las herramientas `image`,
| `attaches` y `linkTool` no funcionan sin un endpoint detrás, y por eso se
| quedaron fuera al migrar el editor a Filament: en v2 sólo quedó `SimpleImage`,
| que incrusta la imagen en el JSON como base64 —lo que hincha la fila de
| `content_page_raw` y no deja nada en el módulo de ficheros.
|
| Detrás de `auth` y del gate del panel: sube ficheros al servidor y hace
| peticiones salientes, así que no puede estar abierto. El throttle acota lo
| segundo, que es lo que un editor podría usar para escanear la red interna
| aunque la comprobación de IP privada ya lo impida.
*/
Route::middleware(['auth', 'can:access-editorjs'])
    ->prefix('admin/editorjs')
    ->name('admin.editorjs.')
    ->group(function () {
        Route::post('/upload', [EditorJsController::class, 'upload'])->name('upload');

        Route::get('/url-metadata', [EditorJsController::class, 'urlMetadata'])
            ->middleware('throttle:30,1')
            ->name('url-metadata');
    });

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

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

    // `POST /file/upload` se retira: su método tenía el cuerpo VACÍO, así que
    // respondía 200 sin subir nada. Las subidas de v2 van por el panel, que
    // valida tipo, tamaño y propiedad y genera las miniaturas.
    Route::get('/download/{module}/{id}/{slug?}', [FileController::class, 'download'])
        ->name('file.download');

    // SEC-04: el ancho llegaba arbitrario y sin caché, así que cada petición
    // reprocesaba la imagen entera y un ancho enorme era un agotamiento de
    // memoria a coste cero para quien lo pidiera. El ancho se resuelve ahora
    // contra la lista de tamaños del proyecto y el resultado se cachea en
    // disco; el throttle cierra la puerta de la calle.
    Route::get('/resize/{module}/{id}/{width}/{slug?}', [FileController::class, 'resizeAndGet'])
        ->middleware('throttle:file-resize')
        ->name('file.resize');

    // N27: `delete` borra del disco y estaba sin autenticar: cualquiera podía
    // barrer los ficheros de otro pasando su id. La comprobación de propiedad
    // va dentro del controlador; el middleware sólo cierra la puerta de la calle.
    Route::post('/delete/{id}', [FileController::class, 'delete'])
        ->middleware('auth')
        ->name('file.delete');

});

// Nota: Las rutas de autenticación se manejan por Laravel Fortify.

// Redirecciones de URLs antiguas /dashboard → /panel (panel Filament tenant).
Route::redirect('/dashboard', '/panel', 301);
Route::redirect('/dashboard/{any}', '/panel', 301)->where('any', '.*');

// Bloqueo explícito del registro: ninguna URL relacionada con registro
// debe responder. Solo el admin da de alta usuarios manualmente.
Route::any('/register', fn () => abort(404));
Route::any('/register/{any}', fn () => abort(404))->where('any', '.*');
Route::any('/panel/register', fn () => abort(404));
Route::any('/panel/register/{any}', fn () => abort(404))->where('any', '.*');

/**
 * Ruta por defecto cuando no se encuentra una petición.
 */
/*
|--------------------------------------------------------------------------
| Newsletter
|--------------------------------------------------------------------------
|
| La página que abre el destinatario desde el correo. El GET **no muta nada**:
| confirmar y darse de baja son POST desde un botón.
|
| Motivo: los clientes de correo y los antivirus hacen prefetch de las URLs de
| los mensajes. Mientras verificar y darse de baja fueron peticiones GET, ese
| prefetch confirmaba suscripciones que nadie había confirmado y daba de baja a
| gente que no lo había pedido.
|
*/
Route::prefix('newsletter')->group(function () {
    Route::get('/{token}', [NewsletterPageController::class, 'show'])
        ->name('newsletter.manage');
    Route::post('/{token}/confirmation', [NewsletterPageController::class, 'confirm'])
        ->name('newsletter.confirm');
    Route::post('/{token}/unsubscription', [NewsletterPageController::class, 'unsubscribe'])
        ->name('newsletter.unsubscribe');
});

Route::fallback(function () {
    return abort(404); // default 404
});
