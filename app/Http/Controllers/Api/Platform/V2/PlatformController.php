<?php

namespace App\Http\Controllers\Api\Platform\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Content\ContentResource;
use App\Http\Resources\V2\PlatformResource;
use App\Models\Content\ContentAvailableType;
use App\Services\Content\ContentService;
use App\Services\Platform\PlatformService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de plataformas para API V2.
 */
class PlatformController extends BaseApiController
{
    public function __construct(
        private PlatformService $service,
        private ContentService $contentService,
    ) {}

    /**
     * Lista todas las plataformas.
     */
    public function index(): JsonResponse
    {
        $platforms = $this->service->getAll();

        return PlatformResource::collection($platforms)->response();
    }

    /**
     * Muestra una plataforma por slug.
     */
    public function show(string $slug): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        return $this->successResponse(new PlatformResource($platform));
    }

    /**
     * Devuelve contenido destacado de una plataforma.
     */
    public function featured(string $slug): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        $featured = $this->contentService->getFeaturedForPlatform($platform);

        return $this->successResponse(
            ContentResource::collection($featured)
        );
    }

    /**
     * Devuelve el contenido de una plataforma filtrado por tipo (slug del tipo).
     */
    public function contentByType(Request $request, string $slug, string $contentType): JsonResponse
    {
        $platform = $this->service->getBySlug($slug);

        if (! $platform) {
            return $this->notFoundResponse('Plataforma no encontrada');
        }

        $type = ContentAvailableType::where('slug', $contentType)->first();

        if (! $type) {
            return $this->notFoundResponse('Tipo de contenido no reconocido');
        }

        $perPage = (int) ($request->integer('per_page') ?: 15);
        $contents = $this->contentService->getByTypeForPlatform($platform, $type->id, $perPage);

        return ContentResource::collection($contents)->response();
    }

    /**
     * Devuelve las categorías disponibles para una plataforma.
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
