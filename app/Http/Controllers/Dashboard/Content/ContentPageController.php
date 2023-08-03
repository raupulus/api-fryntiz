<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class ContentAvailableCategoryController
 * @package App\Http\Controllers\Dashboard\Content
 */
class ContentPageController extends Controller
{

    /**
     * Elimina de forma segura una página y redirecciona al editor.
     *
     * @param ContentPage $page
     * @return RedirectResponse
     */
    public function safeDestroy(ContentPage $page): RedirectResponse
    {
        $content = $page->contentModel;

        ## Almaceno si elimina correctamente la página.
        $deleted = $page->safeDelete();

        $firstPage = $content->pages->first();

        if (!$firstPage) {
            $firstPage = $content->addPage();
        }

        return redirect()->to(route('dashboard.content.edit', $content->id) . '?currentPage=' . $firstPage->id);
    }


    /**
     * Procesa la petición AJAX para actualizar la imagen de la página.
     *
     * @param Request $request
     * @param ContentPage $contentPage
     *
     * @return JsonResponse
     */
    public function ajaxUpdateImage(Request $request, ContentPage $contentPage): JsonResponse
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $isValid = $request->file('image')?->isValid() && (($request->file('image') instanceof \Illuminate\Http\UploadedFile));

        if ($contentPage && $contentPage->id && $isValid) {
            $image = File::addFile($request->file('image'), 'pages', false,  $contentPage->image_id);


            if ($image && $image->id && ($image->id != $contentPage->image_id))  {
                $contentPage->image_id = $image->id;
                $contentPage->save();
            }

            return \JsonHelper::success([
                'msg' => 'Imagen guardada',
                'url' => $image->url,
            ]);

        }

        return \JsonHelper::failed('Imagen no válida');
    }

    /**
     * Comprueba que el slug sea único.
     * En caso de que el slug sea válido, devuelve un JSON con el slug y un mensaje de éxito.
     * Si se recibe la página actual, se comprueba que el slug sea distinto al actual.
     *
     * @param Request $request
     * @param ContentPage|null $page
     * @return JsonResponse
     */
    public function ajaxCheckSlug(Request $request, ContentPage|null $page = null): JsonResponse
    {
        $slug = \Str::slug($request->get('slug'));

        $responseValid = \JsonHelper::success([
            'msg' => 'Slug válido',
            'slug' => $slug,
            'is_valid' => true,
        ]);

        $responseInvalid = \JsonHelper::success([
            'msg' => 'Slug no válido',
            'slug' => $slug,
            'is_valid' => false,
        ]);

        if (!$slug) {
            return $responseInvalid;
        }

        if ($page && $page->id && $page->slug === $slug) {
            return $responseValid;
        }

        $searchPage = ContentPage::where('slug', $slug)->first();

        if ($searchPage && $searchPage->id) {
            return $responseInvalid;
        }

        return $responseValid;
    }

    /**
     * Comprueba una URL y devuelve los metadatos de la misma.
     *
     * @param Request $request
     * @param ContentPage|null $page Página a la que se quiere asociar la URL.
     *
     * @return JsonResponse
     */
    public function ajaxGetUrlMetadata(Request $request, ContentPage|null $page = null): JsonResponse
    {
        $url = $request->get('url');

        $responseInvalid = \JsonHelper::success([
            'success' => 0,
            'meta' => [],
        ]);

        if (!$url) {
            return $responseInvalid;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);


        $title = preg_match('!<title>(.*?)</title>!i', $result, $matches) ? $matches[1] : '';
        $description = preg_match('!<meta name="description" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        $keywords = preg_match('!<meta name="keywords" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        $imageUrl = preg_match('!<meta name="og:image" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';

        if (! $description) {
            $description = preg_match('!<meta content="(.*?)" name="description"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (! $keywords) {
            $keywords = preg_match('!<meta content="(.*?)" name="keywords"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }


        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta content="(.*?)" name="og:image"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }


        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="twitter:image" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="twitter:image:src" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta content="(.*?)"(.*?) name="twitter:image"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta itemprop="image" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="image" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="thumbnail" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="thumbnailUrl" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="og:image:url" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        if (!$imageUrl) {
            $imageUrl = preg_match('!<meta name="og:image:secure_url" content="(.*?)"(.*?)>!i', $result, $matches) ? $matches[1] : '';
        }

        $images = [];

        preg_match_all('!<img(.*?)src="(.*?)"(.*?)>!i', $result, $matches);

        if (isset($matches[2])) {
            $images = $matches[2];
        }

        if (count($images)) {
            // TODO: comprobar si cada imagen empieza por "/" y añadir el dominio o si es base64

            foreach ($images as $key => $image) {
                $domain = parse_url($url, PHP_URL_HOST);

                if (strpos($image, 'http') !== 0) {
                    $images[$key] = 'https://' . $domain . $image;
                }
            }
        }

        if (!$imageUrl && count($images) > 0) {
            $imageUrl = $images[0];
        }

        if ($imageUrl) {
            $imageUrl = str_replace(' ', '%20', $imageUrl);

            $domain = parse_url($url, PHP_URL_HOST);

            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $domain . $imageUrl;
            }
        }

        //dd($title, $description, $keywords, $imageUrl, $images, $result);

        if (! $title || ! $imageUrl) {
            return $responseInvalid;
        }

        return \JsonHelper::success([
            'success' => 1,
            'link' => $url,
            'meta' => [
                'content_page_id' => $page?->id,
                'title' => trim($title),
                'description' => utf8_encode(trim($description)),
                'keywords' => trim($keywords),
                'images' => $images,
                'image' => [
                    'url' => trim($imageUrl),
                    //'width' => 1200,
                    //'height' => 630,
                ],
            ],
        ]);
    }
}
