<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\ContentPage;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ContentAvailableCategoryController
 * @package App\Http\Controllers\Dashboard\Content
 */
class ContentPageController extends Controller
{

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
}
