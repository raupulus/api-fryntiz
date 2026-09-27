<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Http\Resources\V2\Content\ContentContributorResource;
use App\Http\Resources\V2\Content\ContentFileResource;
use App\Http\Resources\V2\Content\ContentGalleryResource;
use App\Http\Resources\V2\Content\ContentMetadataResource;
use App\Http\Resources\V2\Content\ContentPageIndexResource;
use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\Content\ContentResource;
use App\Http\Resources\V2\Content\ContentSeoResource;
use App\Http\Resources\V2\TechnologyResource;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentPage;
use App\Models\File;
use App\Models\Platform;
use App\Models\Tag;
use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Lo que la API sirve de los contenidos (F9 del plan de contenidos del
 * 2026-09-24; P5, F2, F3, F4/P6, H1 y P7 de la auditoría).
 *
 * - El detalle es ligero: los datos del contenido, el índice de páginas sin
 *   texto y la primera página con su texto. Lo demás, con `?include=` o cada
 *   parte en su ruta.
 * - Sólo se lee lo que se va a enviar: el índice no toca el texto de las
 *   páginas.
 * - Las respuestas montadas van a la caché del servidor con la clave ligada a
 *   `ApiCacheVersion` (sube con cualquier cambio en un contenido, sus partes,
 *   sus taxonomías, sus ficheros o su plataforma) y a la hora: las visitas
 *   que se enseñan y lo programado que va saliendo se refrescan cada hora
 *   como mucho.
 */
class ContentApiService
{
    /**
     * Lo que se puede pedir con `?include=` (o todo con `all`).
     */
    public const INCLUDES = ['seo', 'metadata', 'taxonomies', 'technologies', 'contributors', 'galleries', 'files', 'related', 'pages'];

    /**
     * Slugs que no puede tener un contenido: son rutas de la API.
     */
    public const RESERVED_SLUGS = ['highlights'];

    public const RELATED_LIMIT = 5;

    public const HIGHLIGHT_KINDS = ['featured', 'latest', 'trend'];

    public const TREND_DAYS = 3;

    private const CACHE_HOURS = 2;

    /**
     * Lo que necesita `ContentResource`, para cargarlo con `with()`.
     */
    private const RESOURCE_RELATIONS = ['type', 'status', 'platform', 'seo', 'image.fileType', 'image.thumbnails'];

    /**
     * Contenidos publicados con lo que necesita `ContentResource`.
     *
     * @return Builder<Content>
     */
    public function publishedQuery(): Builder
    {
        return Content::query()
            ->with(self::RESOURCE_RELATIONS)
            ->withCount('pages')
            ->withSum('dailyViews as views_count', 'views')
            ->published();
    }

    /**
     * Un contenido publicado de una plataforma, sólo la fila: cada ruta carga
     * lo suyo, y el detalle sólo si no está ya en la caché.
     */
    public function findPublished(string $platformSlug, string $contentSlug): ?Content
    {
        return Content::query()
            ->published()
            ->whereHas('platform', fn (Builder $query) => $query->where('slug', $platformSlug))
            ->where('slug', $contentSlug)
            ->first();
    }

    /**
     * `?include=seo,files` o `all`. Lo que no se conoce se ignora.
     *
     * @return list<string>
     */
    public static function includes(?string $raw): array
    {
        $asked = array_filter(array_map('trim', explode(',', (string) $raw)));

        if (in_array('all', $asked, true)) {
            return self::INCLUDES;
        }

        return array_values(array_intersect(self::INCLUDES, $asked));
    }

    // ── Detalle ─────────────────────────────────────────────────────────────

    /**
     * El detalle montado, desde la caché si ya estaba.
     *
     * @param  list<string>  $includes
     * @return array<string, mixed>
     */
    public function detail(Content $content, array $includes, Request $request): array
    {
        $key = sprintf(
            'api:content:%d:%d:%s:%s:%s',
            $content->id,
            ApiCacheVersion::current(),
            now()->format('YmdH'),
            implode(',', $includes),
            (string) $request->query('format', '-'),
        );

        return Cache::remember($key, now()->addHours(self::CACHE_HOURS), function () use ($content, $includes, $request): array {
            // Con sus relaciones, páginas y visitas, en una consulta más las
            // de las relaciones (con `loadCount`/`loadSum` serían dos más).
            $content = $this->publishedQuery()->whereKey($content->getKey())->first() ?? $content;

            $data = (new ContentResource($content))->resolve($request);

            if (in_array('pages', $includes, true)) {
                $data['pages'] = ContentPageResource::collection($pages = $this->pagesWithBody($content))->resolve($request);
                $first = $pages->first();
            } else {
                $data['pages'] = ContentPageIndexResource::collection($this->pageIndex($content))->resolve($request);
                $first = $this->pagesWithBody($content, limit: 1)->first();
            }

            $data['first_page'] = $first === null ? null : (new ContentPageResource($first))->resolve($request);

            foreach (array_diff($includes, ['pages']) as $include) {
                $data[$include] = $this->part($content, $include, $request);
            }

            return $data;
        });
    }

    /**
     * Una parte del contenido, ya montada: lo mismo que va en `?include=`.
     */
    public function part(Content $content, string $part, Request $request): mixed
    {
        return match ($part) {
            'seo' => $this->seo($content, $request),
            'metadata' => ($metadata = $content->metadata()->first()) === null ? null : (new ContentMetadataResource($metadata))->resolve($request),
            'taxonomies' => $this->taxonomies($content),
            'technologies' => TechnologyResource::collection(
                $content->technologies()->with(['image.fileType', 'image.thumbnails'])->orderBy('name')->get()
            )->resolve($request),
            'contributors' => ContentContributorResource::collection(
                $content->contributors()->orderBy('name')->get()
            )->resolve($request),
            'galleries' => ContentGalleryResource::collection(
                $content->galleries()->with(['image.fileType', 'image.thumbnails', 'images.image.fileType', 'images.image.thumbnails'])->get()
            )->resolve($request),
            'files' => ContentFileResource::collection($this->files($content))->resolve($request),
            'related' => ContentRelatedResource::collection($this->related($content))->resolve($request),
            'pages' => ContentPageResource::collection($this->pagesWithBody($content))->resolve($request),
            default => null,
        };
    }

    /**
     * El índice de páginas, sin leer su texto.
     *
     * @return Collection<int, ContentPage>
     */
    public function pageIndex(Content $content): Collection
    {
        return ContentPage::query()
            ->where('content_id', $content->id)
            ->select(['id', 'content_id', 'order', 'title', 'slug', 'current_page_raw_id'])
            ->selectRaw("(content IS NOT NULL AND content <> '') AS has_html")
            ->with([
                'currentRawType',
                'raws' => fn ($query) => $query->select(['id', 'content_page_id', 'available_page_raw_id']),
                'raws.availableType',
            ])
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Páginas con su texto, desde la página número `$from` (su orden), como
     * mucho `$limit`.
     *
     * @return Collection<int, ContentPage>
     */
    public function pagesWithBody(Content $content, ?int $from = null, ?int $limit = null): Collection
    {
        return ContentPage::query()
            ->where('content_id', $content->id)
            ->with(['currentRawType', 'raws.availableType'])
            ->when($from !== null, fn (Builder $query) => $query->where('order', '>=', $from))
            ->orderBy('order')
            ->orderBy('id')
            ->when($limit !== null, fn (Builder $query) => $query->limit((int) $limit))
            ->get();
    }

    public function pageByOrder(Content $content, int $order): ?ContentPage
    {
        return $this->pagesWithBody($content, $order, 1)->firstWhere('order', $order);
    }

    public function pageBySlug(Content $content, string $slug): ?ContentPage
    {
        return ContentPage::query()
            ->where('content_id', $content->id)
            ->where('slug', $slug)
            ->with(['currentRawType', 'raws.availableType'])
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function seo(Content $content, Request $request): ?array
    {
        $seo = $content->seo()->with(['image.fileType', 'image.thumbnails'])->first();

        return $seo === null ? null : (new ContentSeoResource($seo))->resolve($request);
    }

    /**
     * Categorías (con la principal marcada), subcategorías y etiquetas de la
     * plataforma del contenido. Las filas borradas de los pivotes no cuentan.
     *
     * @return array{categories: list<array<string, mixed>>, subcategories: list<array<string, mixed>>, tags: list<array<string, mixed>>}
     */
    private function taxonomies(Content $content): array
    {
        $categories = Category::query()
            ->join('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->join('content_categories', 'content_categories.platform_category_id', '=', 'platform_categories.id')
            ->leftJoin('categories as parents', 'parents.id', '=', 'categories.parent_id')
            ->where('content_categories.content_id', $content->id)
            ->whereNull('content_categories.deleted_at')
            ->where('platform_categories.platform_id', $content->platform_id)
            ->orderBy('categories.name')
            ->get([
                'categories.id', 'categories.slug', 'categories.name', 'categories.color', 'categories.icon',
                'categories.parent_id', 'parents.slug as parent_slug', 'content_categories.is_main',
            ]);

        $tags = Tag::query()
            ->join('platform_tags', 'platform_tags.tag_id', '=', 'tags.id')
            ->join('content_tags', 'content_tags.platform_tag_id', '=', 'platform_tags.id')
            ->where('content_tags.content_id', $content->id)
            ->whereNull('content_tags.deleted_at')
            ->where('platform_tags.platform_id', $content->platform_id)
            ->orderBy('tags.name')
            ->get(['tags.id', 'tags.slug', 'tags.name', 'tags.color']);

        $category = fn (Category $row): array => [
            'id' => $row->id,
            'slug' => $row->slug,
            'name' => $row->name,
            'color' => $row->color,
            'icon' => $row->icon,
            'is_main' => (bool) $row->getAttribute('is_main'),
        ];

        return [
            'categories' => $categories->whereNull('parent_id')->map($category)->values()->all(),
            'subcategories' => $categories->whereNotNull('parent_id')
                ->map(fn (Category $row): array => [...$category($row), 'parent' => $row->getAttribute('parent_slug')])
                ->values()
                ->all(),
            'tags' => $tags->map(fn (Tag $tag): array => ['id' => $tag->id, 'slug' => $tag->slug, 'name' => $tag->name, 'color' => $tag->color])->all(),
        ];
    }

    /**
     * Los ficheros del contenido que siguen en uso (los marcados como sin usar
     * esperan a borrarse, C2).
     *
     * @return Collection<int, File>
     */
    public function files(Content $content): Collection
    {
        return File::query()
            ->with('fileType')
            ->whereIn('id', ContentFile::query()->where('content_id', $content->id)->whereNull('unused_since')->select('file_id'))
            ->orderBy('id')
            ->get();
    }

    /**
     * Relacionados (F2): primero los elegidos a mano, en el orden en que se
     * vincularon; si faltan, se completa con los publicados más recientes de la
     * misma plataforma y tipo.
     *
     * @return Collection<int, Content>
     */
    public function related(Content $content, int $limit = self::RELATED_LIMIT): Collection
    {
        $compact = ['type', 'image.fileType', 'image.thumbnails'];

        $chosen = Content::query()
            ->with($compact)
            ->published()
            ->join('content_related', 'content_related.content_related_id', '=', 'contents.id')
            ->where('content_related.content_id', $content->id)
            ->whereNull('content_related.deleted_at')
            ->where('contents.platform_id', $content->platform_id)
            ->orderBy('content_related.id')
            ->limit($limit)
            ->get(['contents.*']);

        if ($chosen->count() >= $limit) {
            return $chosen;
        }

        $fill = Content::query()
            ->with($compact)
            ->published()
            ->where('platform_id', $content->platform_id)
            ->where('type_id', $content->type_id)
            ->whereNotIn('id', [$content->id, ...$chosen->modelKeys()])
            ->orderByDesc('published_at')
            ->limit($limit - $chosen->count())
            ->get();

        return $chosen->concat($fill);
    }

    // ── Listados ────────────────────────────────────────────────────────────

    /**
     * Destacados, últimos y tendencia de una plataforma (P7), agrupados por el
     * slug del tipo de contenido, como en `main`.
     *
     * @param  list<string>  $kinds
     * @return array<string, array<string, list<array<string, mixed>>>>
     */
    public function highlights(Platform $platform, array $kinds, int $limit, Request $request): array
    {
        $key = sprintf('api:highlights:%d:%d:%s:%s:%d', $platform->id, ApiCacheVersion::current(), now()->format('YmdH'), implode(',', $kinds), $limit);

        return Cache::remember($key, now()->addHours(self::CACHE_HOURS), function () use ($platform, $kinds, $limit, $request): array {
            // Una consulta por grupo con los primeros de cada tipo, y las
            // relaciones de todos a la vez: el número de consultas no crece
            // con los tipos de contenido que tenga la plataforma.
            $groups = [];

            foreach ($kinds as $kind) {
                $groups[$kind] = $this->highlightQuery($platform, $kind, $limit)->get();
            }

            (new Collection(array_merge(...array_map(fn (Collection $items): array => $items->all(), array_values($groups)))))
                ->load(['type', 'image.fileType', 'image.thumbnails']);

            $result = [];

            foreach ($groups as $kind => $items) {
                $result[$kind] = $items
                    ->groupBy(fn (Content $content): string => (string) $content->type?->slug)
                    ->map(fn (Collection $byType): array => ContentRelatedResource::collection($byType)->resolve($request))
                    ->all();
            }

            return $result;
        });
    }

    /**
     * Los primeros `$limit` de cada tipo de contenido, por tipo y en su orden.
     *
     * @return Builder<Content>
     */
    private function highlightQuery(Platform $platform, string $kind, int $limit): Builder
    {
        $order = match ($kind) {
            'featured', 'latest' => 'contents.published_at DESC',
            // Los más vistos de los últimos días; sin visitas, 0, y a igualdad
            // el más reciente.
            default => '(SELECT COALESCE(SUM(views), 0) FROM content_daily_views'
                .' WHERE content_daily_views.content_id = contents.id AND content_daily_views.date >= ?) DESC, contents.published_at DESC',
        };

        $ranked = Content::query()
            ->published()
            ->where('contents.platform_id', $platform->id)
            // Como en `main`: los últimos sin los destacados, que ya salen aparte.
            ->when($kind === 'featured', fn (Builder $query) => $query->where('contents.is_featured', true))
            ->when($kind === 'latest', fn (Builder $query) => $query->where('contents.is_featured', false))
            ->select('contents.*')
            ->selectRaw(
                "ROW_NUMBER() OVER (PARTITION BY contents.type_id ORDER BY {$order}) AS highlight_rank",
                $kind === 'trend' ? [now()->subDays(self::TREND_DAYS)->toDateString()] : [],
            );

        return Content::query()
            ->fromSub($ranked, 'contents')
            ->where('highlight_rank', '<=', $limit)
            ->orderBy('type_id')
            ->orderBy('highlight_rank');
    }
}
