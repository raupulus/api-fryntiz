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
        /*
        $request->validate([
            'content_page_id' => 'required|integer|exists:content_pages,id',
            'image' => 'required|image',
        ]);
        */

        //$image = $request->file('image');


        $isValid = $request->file('image')->isValid() && (($request->file('image') instanceof \Illuminate\Http\UploadedFile));

        if ($isValid) {
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




        return \JsonHelper::success([
            'test' => 'LLEGA',
            'request' => $request->all(),
            'contentPageId' => $contentPage->id,
            'hasFile' => $request->hasFile('image'),
            'isUploadContent' => $request->hasFile('image') && $request->file('image') instanceof \Illuminate\Http\UploadedFile,
            'isImage' => $request->hasFile('image') && $request->file('image')->isValid(),
            'mime' => ($request->hasFile('image') && $request->file('image')->isValid()) ? $request->file('image')->getMimeType() : false,
        ]);
    }
}
