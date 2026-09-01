<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Content\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\Content\ContentResource;
use App\Jobs\ProcessContentViewJob;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contenidos, siempre colgando de su plataforma.
 *
 * Dos cosas que arregla la URL anidada:
 *
 *  1. **N205.** `pages`, `pages/{order}` y `related` resolvían el contenido
 *     buscando **sólo por slug y sin filtrar por estado**. Devolvían el cuerpo
 *     completo de borradores, programados y archivados, sin token y sin
 *     throttle, y los slugs son el título en kebab-case, o sea adivinables.
 *     Ahora los cuatro métodos pasan por el mismo resolutor, que exige
 *     plataforma **y** estado publicado.
 *  2. **N206.** `contents.slug` era único global, así que dos plataformas no
 *     podían tener un artículo con el mismo slug. Con la URL anidada el par
 *     (plataforma, slug) es lo que desambigua, y el índice pasa a serlo también.
 */
class ContentController extends BaseApiController
{
    public function __construct(private readonly ContentService $service) {}

    /**
     * Contenidos publicados de una plataforma.
     *
     * Sustituye a `/{platform}/featured` y `/{platform}/content/type/{t}`, que
     * eran dos rutas para dos filtros de la misma colección.
     *
     *   ?featured=1        sólo destacados
     *   ?type=tutorial     por slug del tipo de contenido
     *   ?sort=-published_at
     */
    public function index(Request $request, string $platformSlug): JsonResponse
    {
        $platform = $this->platformBySlug($platformSlug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        $query = Content::query()
            ->with(['type', 'status', 'seo', 'metadata', 'image.fileType'])
            // Los dos contadores que el resource expone se resuelven aquí, en
            // agregados de la misma consulta: sin esto salían `views_count` a 0
            // y `pages_count` ausente para siempre, porque nadie los contaba.
            ->withCount('pages')
            ->withSum('dailyViews as views_count', 'views')
            ->forPlatform($platform->id)
            ->published();

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->filled('type')) {
            $typeId = ContentAvailableType::query()
                ->where('slug', $request->query('type'))
                ->value('id');

            if ($typeId === null) {
                return $this->notFoundResponse('Tipo de contenido no reconocido');
            }

            $query->ofType((int) $typeId);
        }

        $collectionQuery = new CollectionQuery(
            filterable: ['is_featured', 'type_id', 'published_at', 'created_at'],
            sortable: ['published_at', 'created_at', 'title'],
            defaultSortColumn: 'published_at',
        );

        return $this->paginatedResponse($collectionQuery->paginate($query, $request), ContentResource::class);
    }

    /**
     * Un contenido publicado.
     */
    public function show(string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        // La visita se cuenta en cola: el visitante no espera a la escritura.
        ProcessContentViewJob::dispatch($content->id, now());

        return $this->successResponse(new ContentResource($content));
    }

    /**
     * Páginas de un contenido publicado.
     */
    public function pages(string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(
            ContentPageResource::collection($content->pages()->orderBy('order')->get())->resolve()
        );
    }

    /**
     * Una página concreta, por su orden dentro del contenido.
     */
    public function page(string $platformSlug, string $contentSlug, int $order): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $page = $content->pages()->where('order', $order)->first();

        if (! $page) {
            return $this->notFoundResponse('Pagina no encontrada');
        }

        return $this->successResponse(new ContentPageResource($page));
    }

    /**
     * Contenidos relacionados, también publicados.
     */
    public function related(string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(
            ContentRelatedResource::collection($this->service->getRelated($content))->resolve()
        );
    }

    private function platformBySlug(string $slug): ?Platform
    {
        return Platform::query()->where('slug', $slug)->first();
    }
}
