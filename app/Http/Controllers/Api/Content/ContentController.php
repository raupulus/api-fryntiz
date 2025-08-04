<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentPageResource;
use App\Http\Resources\ContentRelatedResource;
use App\Http\Resources\ContentResource;
use App\Jobs\ProcessContentViewJob;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentRelated;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonHelper;

class ContentController extends Controller
{
    /**
     * Retrieves the pages from the specified content and processes them.
     *
     * @param Request $request
     * @param Content $content The content object.
     * @return JsonResponse  The JSON response containing the processed pages.
     */
    public function index(Request $request, Content $content): JsonResponse
    {
        //$request->get('content_type') ?? $request->merge(['content_type' => $type]);
        $request->merge(['content_type' => 'json']);

        $pages = ContentPageResource::collection($content->pages);

        return JsonHelper::success([
            'pages' => $pages,
        ]);
    }

    /**
     * Devuelve un contenido a partir de un slug recibido.
     *
     * @param Platform $platform
     * @param Content $content
     * @return JsonResponse
     */
    public function getContentBySlug(Platform $platform, Content $content): JsonResponse
    {
        if (!$platform->id) {
            return JsonHelper::failed('Platform not found');
        }

        if (!$content->id) {
            return JsonHelper::failed('Content not found');
        }

        ## Pone job en cola para procesar la visita aumentando contador
        dispatch(new ProcessContentViewJob($content->id, now()));

        $content->load(['metadata']);

        return JsonHelper::success([
            'ok' => true,
            'content' => ContentResource::make($content),
        ]);
    }

    /**
     * Retrieve the content of a page.
     *
     * @param Content $content The content instance.
     * @param ContentPage $page The content page instance.
     * @param string $type The type of content (default: 'json').
     *
     * @return JsonResponse The JSON response containing the page content.
     */
    public function show(Content $content, ContentPage $page, string $type = 'json'): JsonResponse
    {
        return JsonHelper::success([
            'page' => ContentPageResource::make($page)->additional([
                'content_type' => $type
            ])
        ]);
    }

    /**
     * Devuelve el contenido relacionado para el contenido actual
     *
     * @param Content $content
     * @return JsonResponse
     */
    public function relatedContent(Content $content): JsonResponse
    {
        $contentRelated = $content->contentsRelated()
            ->where('contents.id', '!=', $content->id)
            ->limit(10)
            ->get();

        $contentRelatedMe = $content->contentsRelatedMe()
            ->where('contents.id', '!=', $content->id)
            ->limit(10)
            ->get();

        ## Combino colecciones
        $contentRelatedAll = $contentRelated->merge($contentRelatedMe);

        ## Elimino duplicados por ID (en caso de que haya relaciones bidireccionales)
        $contentRelatedAll = $contentRelatedAll->unique('id');

        ## Mezclo aleatoriamente y tomo máximo 5 elementos
        $relatedRandom = $contentRelatedAll->shuffle()->take(5);

        $related = ContentRelatedResource::collection($relatedRandom);

        return JsonHelper::success([
            'related' => $related,
        ]);
    }
}
