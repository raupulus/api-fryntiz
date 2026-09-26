<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Historial de versiones de las páginas (G5 de la auditoría de contenidos del
 * 2026-09-24).
 *
 * Una versión es lo que había **antes** de un cambio: la crea
 * `ContentPageFormatService` al guardar, sólo si el contenido cambia (mismo
 * contenido con otro título o con otra marca de tiempo de Editor.js no cuenta).
 * Como mucho `MAX_PER_PAGE` por página, y las de más de `MAX_AGE_DAYS` días las
 * borra `content:prune-drafts-and-versions`.
 */
class ContentPageHistoryService
{
    public const MAX_PER_PAGE = 50;

    public const MAX_AGE_DAYS = 30;

    /**
     * Huella del contenido de una página, para saber si ha cambiado.
     *
     * En Editor.js no cuenta `time` (el editor lo cambia en cada `save()`, sin
     * que cambie nada) ni cómo esté indentado el JSON; en Markdown y HTML, los
     * saltos de línea de Windows.
     */
    public static function hash(ContentPageFormatEnum $format, string $content): string
    {
        return hash('sha256', $format->value."\n".self::canonical($format, $content));
    }

    /**
     * Guarda en el historial una versión anterior de la página y deja como
     * mucho `MAX_PER_PAGE`.
     */
    public function record(
        ContentPage $page,
        ContentPageFormatEnum $format,
        string $content,
        ?string $title,
        ContentPageVersionReasonEnum $reason,
        ?User $user = null,
    ): ContentPageVersion {
        $version = ContentPageVersion::query()->create([
            'content_page_id' => $page->id,
            'user_id' => $user?->id,
            'format' => $format,
            'title' => $title,
            'content' => $content,
            'content_hash' => self::hash($format, $content),
            'reason' => $reason,
        ]);

        $this->trim($page->id);

        return $version;
    }

    /**
     * La más reciente, que es la que ofrece «Recuperar versión anterior».
     */
    public function latest(ContentPage $page): ?ContentPageVersion
    {
        return $this->query($page->id)->first();
    }

    /**
     * Todas, de la más reciente a la más antigua.
     *
     * @return Collection<int, ContentPageVersion>
     */
    public function all(ContentPage $page): Collection
    {
        return $this->query($page->id)->with('user')->get();
    }

    /**
     * Una versión de ESTA página: el id suele venir de un formulario y no se da
     * por bueno.
     */
    public function find(ContentPage $page, mixed $id): ?ContentPageVersion
    {
        if (! is_numeric($id)) {
            return null;
        }

        return ContentPageVersion::query()->where('content_page_id', $page->id)->find((int) $id);
    }

    /**
     * Borra las versiones de más de `MAX_AGE_DAYS` días y las que sobran de las
     * `MAX_PER_PAGE` más recientes de cada página. Devuelve las borradas por
     * antiguas, las borradas por exceso y los contenidos afectados.
     *
     * @return array{old: int, excess: int, content_ids: list<int>}
     */
    public function prune(): array
    {
        $old = ContentPageVersion::query()->where('created_at', '<', now()->subDays(self::MAX_AGE_DAYS));
        $contentIds = $this->contentIdsOf((clone $old)->pluck('content_page_id')->all());
        $deletedOld = $old->delete();

        $deletedExcess = 0;
        $crowded = ContentPageVersion::query()
            ->select('content_page_id')
            ->groupBy('content_page_id')
            ->havingRaw('count(*) > ?', [self::MAX_PER_PAGE])
            ->pluck('content_page_id');

        foreach ($crowded as $pageId) {
            $deletedExcess += $this->trim((int) $pageId);
        }

        $contentIds = array_values(array_unique([...$contentIds, ...$this->contentIdsOf($crowded->all())]));

        return ['old' => (int) $deletedOld, 'excess' => $deletedExcess, 'content_ids' => $contentIds];
    }

    /**
     * Deja las `MAX_PER_PAGE` más recientes de una página.
     *
     * @return int Las borradas.
     */
    private function trim(int $pageId): int
    {
        $keep = $this->query($pageId)->limit(self::MAX_PER_PAGE)->pluck('id');

        return ContentPageVersion::query()
            ->where('content_page_id', $pageId)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    /**
     * @return Builder<ContentPageVersion>
     */
    private function query(int $pageId): Builder
    {
        return ContentPageVersion::query()
            ->where('content_page_id', $pageId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array<int, mixed>  $pageIds
     * @return list<int>
     */
    private function contentIdsOf(array $pageIds): array
    {
        if ($pageIds === []) {
            return [];
        }

        return DB::table('content_pages')
            ->whereIn('id', array_unique($pageIds))
            ->whereNotNull('content_id')
            ->distinct()
            ->pluck('content_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private static function canonical(ContentPageFormatEnum $format, string $content): string
    {
        if ($format !== ContentPageFormatEnum::EditorJs) {
            return str_replace("\r\n", "\n", $content);
        }

        $document = json_decode($content, true);

        if (! is_array($document)) {
            return $content;
        }

        unset($document['time']);

        return (string) json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
