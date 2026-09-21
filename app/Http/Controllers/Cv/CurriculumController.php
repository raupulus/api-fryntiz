<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\CV\Curriculum;
use App\Services\Cv\CurriculumPdfService;
use App\Services\Cv\CurriculumService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Currículums en web.
 *
 * Antes había un único método que servía `public/pdf/curriculum_vitae.pdf`: un
 * fichero estático que no cambiaba al editar el CV en el panel. Editabas y
 * seguías repartiendo el PDF de hace años sin que nada lo indicara.
 *
 * Ahora el PDF se genera desde la base de datos y se guarda; aquí sólo se
 * entrega el que haya (B5).
 */
class CurriculumController extends Controller
{
    public function __construct(
        private readonly CurriculumService $service,
        private readonly CurriculumPdfService $pdf,
    ) {}

    /**
     * Listado de currículums públicos: una tarjeta por cada uno, para entrar y
     * copiar su enlace o descargarlo.
     */
    public function index(): View
    {
        return view('cv.index', [
            'curricula' => $this->service->publicOnly(),
        ]);
    }

    /**
     * Vista pública de un currículum. Mismo criterio que el PDF: sólo
     * currículums activos y con visibilidad `public` (sin token).
     */
    public function show(string $slug): View
    {
        $cv = $this->service->bySlug($slug);

        if (! $cv || ! $cv->isVisibleTo()) {
            abort(404);
        }

        return view('cv.show', ['cv' => $cv]);
    }

    /**
     * PDF del currículum predeterminado.
     */
    public function defaultPdf(Request $request): BinaryFileResponse|Response
    {
        $cv = $this->service->defaultCurriculum();

        return $cv === null
            ? $this->missingPdf()
            : $this->deliver($cv, $request->boolean('download'));
    }

    /**
     * PDF de un currículum público, por su slug.
     */
    public function pdf(Request $request, string $slug): BinaryFileResponse|Response
    {
        $cv = $this->service->bySlug($slug);

        if (! $cv || ! $cv->isVisibleTo()) {
            abort(404);
        }

        return $this->deliver($cv, $request->boolean('download'));
    }

    /**
     * PDF de un currículum compartido por enlace privado.
     */
    public function sharedPdf(Request $request, string $shareToken): BinaryFileResponse|Response
    {
        $cv = $this->service->byShareToken($shareToken);

        if (! $cv) {
            abort(404);
        }

        return $this->deliver($cv, $request->boolean('download'))->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Entrega el PDF: por defecto se abre en el navegador (`inline`); con
     * `?download=1` se fuerza la descarga (botón «Descargar PDF» de la vista).
     */
    private function deliver(Curriculum $cv, bool $download = false): BinaryFileResponse|Response
    {
        if (! $cv->is_downloadable) {
            abort(404);
        }

        $path = $this->pdf->absolutePath($cv);

        // Si falta o está marcado para regenerar, se genera aquí mismo: es
        // preferible una descarga lenta a entregar un PDF caducado.
        if ($path === null || $cv->pdf_needs_regeneration) {
            try {
                $this->pdf->generate($cv);
                $path = $this->pdf->absolutePath($cv->refresh());
            } catch (\Throwable) {
                // Si no se puede generar pero hay uno viejo, mejor el viejo que
                // un error.
                $path ??= null;
            }
        }

        if ($path === null) {
            return $this->missingPdf();
        }

        return response()->download($path, $this->fileName($cv), [], $download ? 'attachment' : 'inline');
    }

    /**
     * Nombre del fichero: el de la persona y el slug del CV
     * («raul-caro-pastorino-desarrollador-backend.pdf»), no el titular entero,
     * que con los separadores «·» daba nombres larguísimos.
     */
    private function fileName(Curriculum $cv): string
    {
        return str(($cv->user->full_name ?? '').' '.$cv->slug)->slug().'.pdf';
    }

    private function missingPdf(): Response
    {
        return response('El curriculum en PDF no esta disponible ahora mismo.', 404);
    }
}
