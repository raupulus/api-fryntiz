<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageRaw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return  JsonHelper::success([
            'pages' => $pages,
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

        // TODO: Cachear petición de la página -> ¿Redsys?

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
                'images' => [
                    'medium' => $page->urlImageMedium,
                    'normal' => $page->urlImageNormal,
                    'large' => $page->urlImageLarge,
                ]
            ]
        ]);
    }

}
