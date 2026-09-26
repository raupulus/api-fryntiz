<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\ContentFile;
use App\Models\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ficheros de contenido que ya no usa nada (C2 de la auditoría de contenidos
 * del 2026-09-24).
 *
 * Un fichero del contenido (`content_files`) está en uso si aparece en alguna
 * de sus páginas —también las de la papelera, que se pueden restaurar—, en
 * alguna versión del historial, en algún borrador o como portada (del
 * contenido, de una página, de su SEO o de un borrador). Si no, se marca con
 * `unused_since`, y si vuelve a aparecer se desmarca. `purge()` borra los que
 * llevan `PURGE_AFTER_DAYS` días marcados.
 *
 * Se reconoce un fichero por su `file_id` en el JSON de Editor.js y por sus
 * URLs (`/file/get|download|resize/{módulo}/{id}` y las de sus miniaturas,
 * `/file/thumbnail/get/{módulo}/{id de la miniatura}`), también con las barras
 * escapadas del JSON: así se ven los de las páginas en Markdown y HTML.
 */
class ContentFileUsageService
{
    public const PURGE_AFTER_DAYS = 30;

    /**
     * Tres grupos: `file_id`, id del fichero en su URL, id de una miniatura.
     */
    private const REFERENCE_PATTERN = '"file_id"\s*:\s*"?([0-9]+)'
        .'|file\\\\?/(?:get|download|resize)\\\\?/[A-Za-z0-9_-]+\\\\?/([0-9]+)'
        .'|file\\\\?/thumbnail\\\\?/get\\\\?/[A-Za-z0-9_-]+\\\\?/([0-9]+)';

    /**
     * Marca y desmarca los ficheros de un contenido según se usen o no.
     */
    public function refresh(?int $contentId): void
    {
        if ($contentId === null) {
            return;
        }

        $used = $this->usedFileIds($contentId);

        ContentFile::query()
            ->where('content_id', $contentId)
            ->whereNotNull('unused_since')
            ->whereIn('file_id', $used)
            ->update(['unused_since' => null]);

        ContentFile::query()
            ->where('content_id', $contentId)
            ->whereNull('unused_since')
            ->whereNotIn('file_id', $used)
            ->update(['unused_since' => now()]);
    }

    /**
     * Al eliminar un contenido definitivamente, todos sus ficheros quedan sin
     * usar. Las filas se quedan (su clave pasa a nula) para que `purge()` los
     * borre.
     */
    public function markAllOf(int $contentId): void
    {
        ContentFile::query()
            ->where('content_id', $contentId)
            ->whereNull('unused_since')
            ->update(['unused_since' => now()]);
    }

    /**
     * Ids de los ficheros que usa un contenido.
     *
     * @return list<int>
     */
    public function usedFileIds(int $contentId): array
    {
        $covers = DB::table('contents')->where('id', $contentId)->pluck('image_id')
            ->merge(DB::table('content_pages')->where('content_id', $contentId)->pluck('image_id'))
            ->merge(DB::table('content_seo')->where('content_id', $contentId)->pluck('image_id'))
            ->merge(DB::table('content_page_drafts')->where('content_id', $contentId)->pluck('image_id'));

        return $this->unique([...$this->referencedInTexts($contentId), ...$covers->all()]);
    }

    /**
     * Borra los ficheros marcados hace más de `$days` días: el fichero, sus
     * miniaturas y su fila.
     *
     * Antes de borrar se vuelve a comprobar, en toda la base, que nada lo usa:
     * otro contenido que lo tenga en uso, el texto de cualquier página,
     * versión o borrador, o cualquier columna con clave foránea a `files`
     * (portadas, galerías, usuarios…). Si algo lo usa, se desmarca y no se
     * borra.
     *
     * @return array{deleted: int, kept: int}
     */
    public function purge(int $days = self::PURGE_AFTER_DAYS): array
    {
        $candidates = ContentFile::query()
            ->whereNotNull('unused_since')
            ->where('unused_since', '<=', now()->subDays($days))
            ->distinct()
            ->pluck('file_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($candidates === []) {
            return ['deleted' => 0, 'kept' => 0];
        }

        $inUse = [
            ...ContentFile::query()->whereIn('file_id', $candidates)->whereNull('unused_since')->pluck('file_id')->all(),
            ...$this->referencedInTexts(null),
            ...$this->referencedByForeignKeys($candidates),
        ];
        $inUse = array_flip($this->unique($inUse));

        $deleted = 0;
        $kept = 0;

        foreach ($candidates as $fileId) {
            if (isset($inUse[$fileId])) {
                ContentFile::query()->where('file_id', $fileId)->update(['unused_since' => null]);
                $kept++;

                continue;
            }

            $file = File::query()->find($fileId);

            // Sin fichero no hay fila en `content_files` (clave en cascada).
            if ($file === null) {
                continue;
            }

            // Borra las miniaturas, el fichero del disco y la fila; las de
            // `content_files` se van con ella (clave en cascada).
            if ($file->safeDelete()) {
                $deleted++;
            } else {
                Log::warning("content:purge-unused-files: no se ha podido borrar el fichero {$fileId}.");
            }
        }

        return ['deleted' => $deleted, 'kept' => $kept];
    }

    /**
     * Ficheros nombrados en el texto de las páginas (también las de la
     * papelera y sus formatos guardados), versiones y borradores, de un
     * contenido o de todos (`null`).
     *
     * @return list<int>
     */
    private function referencedInTexts(?int $contentId): array
    {
        $scope = $contentId === null ? '' : 'WHERE p.content_id = ?';
        $draftScope = $contentId === null ? '' : 'WHERE d.content_id = ?';
        $bindings = $contentId === null ? [self::REFERENCE_PATTERN] : [self::REFERENCE_PATTERN, $contentId, $contentId, $contentId, $contentId];

        $rows = DB::select(<<<SQL
            SELECT DISTINCT m[1] AS file_id, m[2] AS url_file_id, m[3] AS thumbnail_id
            FROM (
                SELECT regexp_matches(texts.body, ?, 'g') AS m
                FROM (
                    SELECT p.content AS body FROM content_pages p {$scope}
                    UNION ALL
                    SELECT r.content FROM content_page_raw r JOIN content_pages p ON p.id = r.content_page_id {$scope}
                    UNION ALL
                    SELECT v.content FROM content_page_versions v JOIN content_pages p ON p.id = v.content_page_id {$scope}
                    UNION ALL
                    SELECT d.content FROM content_page_drafts d {$draftScope}
                ) texts
                WHERE texts.body IS NOT NULL
            ) matches
            SQL, $bindings);

        $ids = [];
        $thumbnails = [];

        foreach ($rows as $row) {
            foreach ([$row->file_id, $row->url_file_id] as $id) {
                if ($id !== null) {
                    $ids[] = (int) $id;
                }
            }

            if ($row->thumbnail_id !== null) {
                $thumbnails[] = (int) $row->thumbnail_id;
            }
        }

        if ($thumbnails !== []) {
            $ids = [...$ids, ...DB::table('file_thumbnails')->whereIn('id', array_unique($thumbnails))->pluck('file_id')->all()];
        }

        return $this->unique($ids);
    }

    /**
     * De estos ficheros, los que alguna tabla apunta con clave foránea a
     * `files`, salvo `content_files` y las miniaturas (que se borran con el
     * fichero).
     *
     * @param  list<int>  $fileIds
     * @return list<int>
     */
    private function referencedByForeignKeys(array $fileIds): array
    {
        $columns = DB::select(<<<'SQL'
            SELECT kcu.table_name, kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON kcu.constraint_name = tc.constraint_name AND kcu.table_schema = tc.table_schema
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = current_schema()
              AND ccu.table_name = 'files' AND ccu.column_name = 'id'
            SQL);

        $found = [];

        foreach ($columns as $column) {
            if (in_array($column->table_name, ['content_files', 'file_thumbnails'], true)) {
                continue;
            }

            $found = [...$found, ...DB::table($column->table_name)->whereIn($column->column_name, $fileIds)->pluck($column->column_name)->all()];
        }

        return $this->unique($found);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function unique(array $ids): array
    {
        return array_values(array_unique(array_map('intval', array_filter($ids, fn (mixed $id): bool => is_numeric($id) && (int) $id > 0))));
    }
}
