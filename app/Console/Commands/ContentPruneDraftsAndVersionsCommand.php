<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Content\ContentFileUsageService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageHistoryService;
use Illuminate\Console\Command;

/**
 * Tarea diaria (F6 del plan de contenidos del 2026-09-24): borradores sin tocar
 * en 30 días, versiones de más de 30 días y las que pasan de 50 por página.
 * Después mira los ficheros de los contenidos afectados: alguno puede haberse
 * quedado sin usar al irse la última versión o borrador que lo nombraba.
 */
class ContentPruneDraftsAndVersionsCommand extends Command
{
    protected $signature = 'content:prune-drafts-and-versions';

    protected $description = 'Borrar borradores y versiones de páginas de más de 30 días, y las versiones que pasan de 50 por página';

    public function handle(ContentPageDraftService $drafts, ContentPageHistoryService $history, ContentFileUsageService $usage): int
    {
        $prunedDrafts = $drafts->prune();
        $prunedVersions = $history->prune();

        $contentIds = array_values(array_unique([...$prunedDrafts['content_ids'], ...$prunedVersions['content_ids']]));

        foreach ($contentIds as $contentId) {
            $usage->refresh($contentId);
        }

        $this->info(sprintf(
            'Borradores borrados: %d. Versiones borradas: %d por antiguas y %d por pasar de %d por página.',
            $prunedDrafts['deleted'],
            $prunedVersions['old'],
            $prunedVersions['excess'],
            ContentPageHistoryService::MAX_PER_PAGE,
        ));

        return self::SUCCESS;
    }
}
