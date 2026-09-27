<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Content\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Content\V2\ContentPagesRequest;
use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\Content\ContentResource;
use App\Jobs\ProcessContentViewJob;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Services\Content\ContentApiService;
use App\Support\ApiCacheVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use function Illuminate\Support\defer;

/**
 * Contenidos, siempre colgando de su plataforma, y sólo los publicados y
 * activos: lo demás responde 404 igual que lo que no existe.
 *
 * El detalle es ligero y cada parte se puede pedir aparte (F9 del plan de
 * contenidos del 2026-09-24): ver `ContentApiService`. Todas estas rutas
 * llevan huella (`ETag`), responden 304 si no ha cambiado y guardan la
 * respuesta montada en la caché del servidor.
 */
class ContentController extends BaseApiController
{
    public function __construct(private readonly ContentApiService $service) {}

    /**
     * Contenidos publicados de una plataforma.
     *
     *   ?featured=1              sólo destacados
     *   ?type=project            por slug del tipo de contenido
     *   ?category= ?tag= ?technology=   por slug
     *   ?q=texto                 en el título o el extracto, sin distinguir mayúsculas
     *   ?sort=-published_at      y los demás de `CollectionQuery`
     */
    public function index(Request $request, string $platformSlug): JsonResponse
    {
        $platform = Platform::query()->where('slug', $platformSlug)->first();

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        $typeId = null;

        if ($request->filled('type')) {
            $typeId = ContentAvailableType::query()->where('slug', $request->query('type'))->value('id');

            if ($typeId === null) {
                return $this->notFoundResponse('Tipo de contenido no reconocido');
            }
        }

        $query = $request->query();
        ksort($query);
        $key = sprintf('api:contents:%d:%d:%s:%s', $platform->id, ApiCacheVersion::current(), now()->format('YmdH'), md5((string) json_encode($query)));

        $payload = Cache::remember($key, now()->addHours(2), function () use ($request, $platform, $typeId): array {
            $query = $this->service->publishedQuery()->where('platform_id', $platform->id);

            if ($request->boolean('featured')) {
                $query->featured();
            }

            if ($typeId !== null) {
                $query->ofType((int) $typeId);
            }

            $this->applyTaxonomyFilters($query, $request, $platform);

            $collectionQuery = new CollectionQuery(
                filterable: ['is_featured', 'type_id', 'published_at', 'created_at'],
                sortable: ['published_at', 'created_at', 'title'],
                defaultSortColumn: 'published_at',
            );

            return $this->paginatedResponse($collectionQuery->paginate($query, $request), ContentResource::class)->getData(true);
        });

        return response()->json($payload);
    }

    /**
     * Destacados, últimos y tendencia, agrupados por tipo.
     *
     *   ?type=featured|latest|trend|all   (por defecto all)
     *   ?limit=6                          por tipo, de 1 a 24
     */
    public function highlights(Request $request, string $platformSlug): JsonResponse
    {
        $platform = Platform::query()->where('slug', $platformSlug)->first();

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        $type = (string) $request->query('type', 'all');
        $kinds = $type === 'all' ? ContentApiService::HIGHLIGHT_KINDS : array_values(array_intersect(ContentApiService::HIGHLIGHT_KINDS, [$type]));

        if ($kinds === []) {
            return $this->errorResponse('El tipo tiene que ser featured, latest, trend o all.', 422);
        }

        $limit = max(1, min(24, (int) $request->query('limit', '6')));

        return $this->successResponse($this->service->highlights($platform, $kinds, $limit, $request));
    }

    /**
     * El detalle de un contenido: sus datos, el índice de páginas sin texto y
     * la primera página. Con `?include=` lo que se pida (`all`, todo).
     *
     * Cada petición suma una visita, también si se responde desde la caché o
     * con 304: se cuenta después de enviar la respuesta, en el mismo proceso,
     * sin depender de la cola (H1). Con `defer()` y no con
     * `dispatchAfterResponse()`: ésa se queda registrada en la aplicación y,
     * si atiende más de una petición (tests, Octane), la repite en cada una.
     */
    public function show(ContentPagesRequest $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $job = new ProcessContentViewJob($content->id, now());
        defer(fn () => $job->handle());

        return $this->successResponse($this->service->detail($content, ContentApiService::includes($request->query('include')), $request));
    }

    /**
     * Las páginas con su texto. `?from=` (número de página) y `?limit=`.
     */
    public function pages(ContentPagesRequest $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $from = $request->filled('from') ? (int) $request->query('from') : null;
        $limit = $request->filled('limit') ? (int) $request->query('limit') : null;

        return $this->successResponse(ContentPageResource::collection($this->service->pagesWithBody($content, $from, $limit))->resolve($request));
    }

    /**
     * Una página, por su número.
     */
    public function page(ContentPagesRequest $request, string $platformSlug, string $contentSlug, int $order): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);
        $page = $content === null ? null : $this->service->pageByOrder($content, $order);

        return $page === null
            ? $this->notFoundResponse($content === null ? 'Contenido no encontrado' : 'Página no encontrada')
            : $this->successResponse((new ContentPageResource($page))->resolve($request));
    }

    /**
     * Una página, por su slug.
     */
    public function pageBySlug(ContentPagesRequest $request, string $platformSlug, string $contentSlug, string $pageSlug): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);
        $page = $content === null ? null : $this->service->pageBySlug($content, $pageSlug);

        return $page === null
            ? $this->notFoundResponse($content === null ? 'Contenido no encontrado' : 'Página no encontrada')
            : $this->successResponse((new ContentPageResource($page))->resolve($request));
    }

    /**
     * Relacionados: los elegidos a mano primero. `?limit=` de 1 a 20.
     */
    public function related(Request $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $limit = max(1, min(20, (int) $request->query('limit', (string) ContentApiService::RELATED_LIMIT)));

        return $this->successResponse(ContentRelatedResource::collection($this->service->related($content, $limit))->resolve($request));
    }

    public function seo(Request $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        return $this->part($request, $platformSlug, $contentSlug, 'seo');
    }

    public function galleries(Request $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        return $this->part($request, $platformSlug, $contentSlug, 'galleries');
    }

    public function files(Request $request, string $platformSlug, string $contentSlug): JsonResponse
    {
        return $this->part($request, $platformSlug, $contentSlug, 'files');
    }

    private function part(Request $request, string $platformSlug, string $contentSlug, string $part): JsonResponse
    {
        $content = $this->service->findPublished($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse($this->service->part($content, $part, $request));
    }

    /**
     * Filtros por slug de categoría, etiqueta y tecnología de la plataforma, y
     * búsqueda de texto (F3). Las filas borradas de los pivotes no cuentan.
     *
     * @param  Builder<Content>  $query
     */
    private function applyTaxonomyFilters(Builder $query, Request $request, Platform $platform): void
    {
        if ($request->filled('category')) {
            $query->whereHas('categoriesJoin.platformCategory', fn (Builder $category) => $category
                ->where('platform_id', $platform->id)
                ->whereHas('category', fn (Builder $row) => $row->where('slug', (string) $request->query('category'))));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tagsJoin.platformTag', fn (Builder $tag) => $tag
                ->where('platform_id', $platform->id)
                ->whereHas('tag', fn (Builder $row) => $row->where('slug', (string) $request->query('tag'))));
        }

        if ($request->filled('technology')) {
            $query->whereHas('technologies', fn (Builder $technology) => $technology->where('technologies.slug', (string) $request->query('technology')));
        }

        if ($request->filled('q')) {
            $text = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
            $query->where(fn (Builder $search) => $search
                ->where('contents.title', 'ilike', $text)
                ->orWhere('contents.excerpt', 'ilike', $text));
        }
    }
}
