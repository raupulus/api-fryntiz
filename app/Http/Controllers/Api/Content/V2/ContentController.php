<?php

namespace App\Http\Controllers\Api\Content\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\Content\ContentResource;
use App\Models\Content\Content;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de contenidos para API V2.
 */
class ContentController extends BaseApiController
{
    public function __construct(private ContentService $service) {}

    /**
     * Muestra un contenido por plataforma y slug.
     */
    public function show(string $platformSlug, string $contentSlug): JsonResponse
    {
        $content = $this->service->getBySlug($platformSlug, $contentSlug);

        if (!$content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(new ContentResource($content));
    }

    /**
     * Devuelve las páginas de un contenido.
     */
    public function pages(string $contentSlug): JsonResponse
    {
        $content = Content::with('pages')->where('slug', $contentSlug)->first();

        if (!$content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(
            ContentPageResource::collection($content->pages)
        );
    }

    /**
     * Devuelve contenido relacionado.
     */
    public function related(string $contentSlug): JsonResponse
    {
        $content = Content::where('slug', $contentSlug)->first();

        if (!$content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $related = $this->service->getRelated($content);

        return $this->successResponse(
            ContentRelatedResource::collection($related)
        );
    }
}
