<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform\V2;

use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\PlatformResource;
use App\Models\Platform;
use App\Services\Platform\PlatformApiService;
use App\Services\Platform\PlatformService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plataformas.
 *
 * `featured` y `content/type/{t}` han desaparecido de aquí: eran dos filtros de
 * la colección de contenidos y viven en `ContentController::index()` como
 * `?featured=1` y `?type=…`.
 */
class PlatformController extends BaseApiController
{
    public function __construct(
        private readonly PlatformService $service,
        private readonly PlatformApiService $api,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new CollectionQuery(
            filterable: ['slug', 'domain', 'created_at'],
            sortable: ['title', 'created_at'],
            defaultSortColumn: 'title',
            defaultSortDescending: false,
        );

        return $this->paginatedResponse(
            $query->paginate(Platform::query()->with('image'), $request),
            PlatformResource::class
        );
    }

    /**
     * La ficha completa de la plataforma (P7): la de la v1, que el frontend
     * usa como primera carga y para los metadatos del SSR. Sin los `*_token`.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        return $this->successResponse($this->api->detail($platform, $request));
    }

    /**
     * Etiquetas de una plataforma, con cuántos contenidos publicados las usan.
     */
    public function tags(string $slug): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        return $this->successResponse($this->api->tags($platform));
    }

    /**
     * Categorías disponibles en una plataforma.
     */
    public function categories(string $slug): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        return $this->successResponse($platform->getApiCategories());
    }
}
