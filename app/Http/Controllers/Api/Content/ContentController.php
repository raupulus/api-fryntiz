<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessContentViewJob;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageRaw;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonHelper;

class ContentController extends Controller
{

    /**
     * Retrieves the pages from the specified content and processes them.
     *
     * @param Content $content The content object.
     * @return JsonResponse  The JSON response containing the processed pages.
     */
    public function index(Content $content): JsonResponse
    {

        $pages = $content->pages;

        // TODO: Procesar las páginas para descartar datos que no necesitamos
        // TODO: Preparar sistema de caché con la query para el contenido
        // TODO: Obtener imagen/thumbnail en base al tamaño del sitio dónde se usará. Tener en cuenta que esto
        //       puede cambiar por cada plataforma en base al sitio/hueco dónde se situará. ¿Dinamizar parámetro de ruta?
        // TODO: Obtener cada página raw con el json? En content viene el html y no suele interesar.

        return JsonHelper::success([
            'pages' => $pages,
        ]);
    }

    public function getContentBySlug(string $slugPlatform, string $slugContent): JsonResponse
    {
        $contentQuery = Content::where('slug', $slugContent)->first();

        if (!$contentQuery) {
            return JsonHelper::failed('Content not found');
        }

        ## Pone job en cola para procesar la visita aumentando contador
        dispatch(new ProcessContentViewJob($contentQuery->id, now()));

        $content = collect([
            'title' => $contentQuery->title,
            'slug' => $contentQuery->slug,
            'excerpt' => $contentQuery->excerpt,
            'is_featured' => $contentQuery->is_featured,
            'has_image' => (bool) $contentQuery->image_id,
            'urlImageSmall' => $contentQuery->urlImageSmall,
            'urlImageMedium' => $contentQuery->urlImageMedium,
            'urlImage' => $contentQuery->urlImageNormal,
            'created_at' => $contentQuery->created_at,
            'updated_at' => $contentQuery->updated_at,
            'created_at_human' => $contentQuery->created_at->translatedFormat('d F Y'),
            'total_pages' => $contentQuery->pages()->count(),
            'categories' => $contentQuery->categoriesQuery()->select(['categories.name', 'categories.slug'])->get(),
            'subcategories' => $contentQuery->subcategoriesQuery()->select(['categories.name', 'categories.slug', 'content_categories.is_main', 'parent_id'])->get()->map(function ($subcat) {
                $subcat->parent = $subcat->parentCategory?->slug;
                unset($subcat->parent_id);
                unset($subcat->parentCategory);

                return $subcat;
            }),
            'tags' => $contentQuery->tagsQuery()->pluck('name'),
            'metadata' => [
                'web' => $contentQuery->metadata?->web,
                'telegram_channel' => $contentQuery->metadata?->telegram_channel,
                'youtube_channel' => $contentQuery->metadata?->youtube_channel,
                'youtube_video' => $contentQuery->metadata?->youtube_video,
                'youtube_video_id' => $contentQuery->metadata?->youtube_video_id,
                'gitlab' => $contentQuery->metadata?->gitlab,
                'github' => $contentQuery->metadata?->github,
                'mastodon' => $contentQuery->metadata?->mastodon,
                'twitter' => $contentQuery->metadata?->urlTwitter,
            ],
            'technologies' => $contentQuery->technologies->map(function ($tech) {
                return [
                    'name' => $tech->name,
                    'slug' => $tech->slug,
                    'urlImageSmall' => $tech->urlImageSmall,
                ];
            }),
            'pages_slug' => $contentQuery->pages()->pluck('slug'),
        ]);

        return JsonHelper::success([
            'ok' => true,
            'content' => $content,
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

        // TODO: Cachear petición de la página -> ¿Redis?

        ## Si el tipo es html, devolvemos $page->content
        if (!$type || $type === 'html') {
            $content = $page->content;
        } else { // Si el tipo es otro, lo buscamos dentro de la tabla: content_available_page_raw
            $contentRaw = ContentPageRaw::where('content_page_raw.content_page_id', $page->id)
                ->leftJoin('content_available_page_raw', 'content_available_page_raw.id', '=', 'content_page_raw.available_page_raw_id')
                ->where('content_available_page_raw.type', $type)
                ->first();


            // TOFIX: Cuando se pida un tipo de contenido en un formato concreto, no se debería devolver otro formato.
            // TODO: Revisar esta parte y crear una respuesta dónde vuelva vacío en ese formato. Por ejemplo: json = {}


            $content = $contentRaw?->content ?? $content->content;
        }

        return JsonHelper::success([
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $content,
                'order' => $page->order,
                'has_image' => (bool) $page->image_id,
                'images' => [
                    'medium' => $page->urlImageMedium,
                    'normal' => $page->urlImageNormal,
                    'large' => $page->urlImageLarge,
                ]
            ]
        ]);
    }

}
