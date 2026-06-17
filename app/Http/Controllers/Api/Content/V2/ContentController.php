<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Content\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\V2\Content\ContentPageResource;
use App\Http\Resources\V2\Content\ContentRelatedResource;
use App\Http\Resources\V2\Content\ContentResource;
use App\Jobs\ProcessContentViewJob;
use App\Models\Content\Content;
use App\Services\Content\ContentService;
use Carbon\Carbon;
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

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        // Registra la vista de forma asíncrona (upsert en content_daily_views).
        ProcessContentViewJob::dispatch($content->id, Carbon::now());

        return $this->successResponse(new ContentResource($content));
    }

    /**
     * Devuelve las páginas de un contenido.
     */
    public function pages(string $contentSlug): JsonResponse
    {
        $content = Content::with('pages')->where('slug', $contentSlug)->first();

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        return $this->successResponse(
            ContentPageResource::collection($content->pages)
        );
    }

    /**
     * Devuelve una página concreta de un contenido por su orden.
     */
    public function page(string $contentSlug, int $order): JsonResponse
    {
        $content = Content::where('slug', $contentSlug)->first();

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
     * Devuelve contenido relacionado.
     */
    public function related(string $contentSlug): JsonResponse
    {
        $content = Content::where('slug', $contentSlug)->first();

        if (! $content) {
            return $this->notFoundResponse('Contenido no encontrado');
        }

        $related = $this->service->getRelated($content);

        return $this->successResponse(
            ContentRelatedResource::collection($related)
        );
    }
}
