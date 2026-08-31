<?php

declare(strict_types=1);

namespace App\Services\Cv;

use App\Models\CV\Curriculum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generación del PDF de un currículum.
 *
 * El flujo es el que pediste (B5): el PDF es un fichero generado y guardado, no
 * se rehace en cada visita. Al tocar cualquier tabla del CV se marca
 * `pdf_needs_regeneration`, hay un botón «Regenerar PDF» en el panel, y un
 * comando nocturno recoge los que se hayan quedado marcados.
 *
 * Lo que había: un fichero estático en `public/pdf/curriculum_vitae.pdf` que se
 * servía siempre igual. Editabas el CV en el panel y el PDF seguía siendo el de
 * hace años, sin que nada lo indicara.
 */
class CurriculumPdfService
{
    private const DISK = 'public';

    private const FOLDER = 'curricula/pdf';

    /**
     * Genera el PDF y lo deja guardado. Devuelve la ruta relativa en el disco.
     */
    public function generate(Curriculum $cv): string
    {
        $cv->loadMissing(CurriculumService::SECTIONS);

        $pdf = Pdf::loadView('cv.pdf', ['cv' => $cv])
            ->setPaper('a4')
            ->setOption(['isRemoteEnabled' => false]);

        $path = self::FOLDER.'/'.$cv->slug.'-'.$cv->id.'.pdf';

        Storage::disk(self::DISK)->put($path, $pdf->output());

        // Se borra el anterior si el slug ha cambiado: si no, cada renombrado
        // dejaría un PDF huérfano en el disco para siempre.
        if ($cv->pdf_path && $cv->pdf_path !== $path) {
            Storage::disk(self::DISK)->delete($cv->pdf_path);
        }

        $cv->forceFill([
            'pdf_path' => $path,
            'pdf_needs_regeneration' => false,
            'pdf_generated_at' => now(),
        ])->saveQuietly();

        return $path;
    }

    /**
     * Regenera todos los currículums marcados.
     *
     * @return array{generados: int, fallidos: array<int, array{id: int, error: string}>}
     */
    public function regeneratePending(): array
    {
        $generated = 0;
        $failed = [];

        Curriculum::query()
            ->where('pdf_needs_regeneration', true)
            ->where('is_active', true)
            ->chunkById(20, function ($curriculums) use (&$generated, &$failed) {
                foreach ($curriculums as $cv) {
                    try {
                        $this->generate($cv);
                        $generated++;
                    } catch (\Throwable $e) {
                        // Un CV que falla no puede impedir que se regeneren los
                        // demás: se anota y se sigue.
                        $failed[] = ['id' => $cv->id, 'error' => $e->getMessage()];
                    }
                }
            });

        return ['generados' => $generated, 'fallidos' => $failed];
    }

    /**
     * Ruta absoluta del PDF guardado, o null si no hay ninguno.
     */
    public function absolutePath(Curriculum $cv): ?string
    {
        if (blank($cv->pdf_path) || ! Storage::disk(self::DISK)->exists($cv->pdf_path)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($cv->pdf_path);
    }
}
