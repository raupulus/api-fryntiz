<?php

declare(strict_types=1);

namespace App\Console\Commands\CV;

use App\Models\CV\Curriculum;
use App\Services\Cv\CurriculumPdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Regenera los PDF de currículum que se hayan quedado marcados.
 *
 * Es la red de seguridad de B5: lo normal es pulsar «Regenerar PDF» en el panel
 * al acabar de editar, pero eso se olvida. El comando pasa cada noche y recoge
 * lo que quedó pendiente.
 */
class RegenerateCurriculumPdfsCommand extends Command
{
    protected $signature = 'cv:regenerate-pdfs {--force : Regenera todos, estén marcados o no}';

    protected $description = 'Regenera los PDF de los curriculums marcados como pendientes';

    public function handle(CurriculumPdfService $service): int
    {
        if ($this->option('force')) {
            Curriculum::query()->update(['pdf_needs_regeneration' => true]);
            $this->info('Marcados todos los curriculums para regenerar.');
        }

        $result = $service->regeneratePending();

        $this->info("PDF regenerados: {$result['generados']}");

        if ($result['fallidos'] !== []) {
            foreach ($result['fallidos'] as $failure) {
                $this->error("CV #{$failure['id']}: {$failure['error']}");
            }

            Log::error('CV: fallos al regenerar PDF', ['fallidos' => $result['fallidos']]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
