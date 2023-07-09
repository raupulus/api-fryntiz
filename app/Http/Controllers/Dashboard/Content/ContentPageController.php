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

        $isValid = $request->file('image')->isValid() && (($request->file('image') instanceof \Illuminate\Http\UploadedFile));

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
}
