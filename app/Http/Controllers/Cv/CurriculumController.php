<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cv;

use App\Http\Controllers\Controller;
use App\Models\CV\Curriculum;
use App\Services\Cv\CurriculumPdfService;
use App\Services\Cv\CurriculumService;
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
     * PDF del currículum predeterminado.
     */
    public function defaultPdf(): BinaryFileResponse|Response
    {
        $cv = $this->service->defaultCurriculum();

        return $cv === null
            ? $this->missingPdf()
            : $this->deliver($cv);
    }

    /**
     * PDF de un currículum público, por su slug.
     */
    public function pdf(string $slug): BinaryFileResponse|Response
    {
        $cv = $this->service->bySlug($slug);

        if (! $cv || ! $cv->isVisibleTo()) {
            abort(404);
        }

        return $this->deliver($cv);
    }

    /**
     * PDF de un currículum compartido por enlace privado.
     */
    public function sharedPdf(string $shareToken): BinaryFileResponse|Response
    {
        $cv = $this->service->byShareToken($shareToken);

        if (! $cv) {
            abort(404);
        }

        return $this->deliver($cv)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function deliver(Curriculum $cv): BinaryFileResponse|Response
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

        return response()->download($path, str($cv->title)->slug().'.pdf', [], 'inline');
    }

    private function missingPdf(): Response
    {
        return response('El curriculum en PDF no esta disponible ahora mismo.', 404);
    }
}
