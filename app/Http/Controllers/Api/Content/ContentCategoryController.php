<?php

namespace App\Http\Controllers\Api\Content;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JsonHelper;

class ContentCategoryController extends Controller
{



    public function index(Request $request, Platform $platform): JsonResponse
    {

        $categories = $platform->categories()
            ->select('categories.id', 'categories.parent_id', 'categories.slug', 'categories.name', 'categories.description', 'categories.icon', 'categories.color')
            ->where('parent_id', null)
            ->with('subcategories', function ($query) {
                $query->select('id', 'parent_id', 'slug', 'name', 'description', 'icon', 'color');
            })
            ->get()
        ;


        // TODO: Las categorías no tienen el helper ni la interfaz para las imágenes


        // TODO: Revisar la forma de obtener subcategorías para optimizar esta parte y quitar esos unset.
        $categories->map(function ($category) {
            unset($category->id);
            unset($category->pivot);
            unset($category->parent_id);

            if ($category->subcategories) {
                $category->subcategories->map(function ($subcategory) use ($category) {
                    unset($subcategory->id);
                    unset($subcategory->parent_id);

                    $subcategory->parent = $category->slug;

                    return $subcategory;
                });
            }


            return $category;
        });


        // TODO: Implementar caché aquí y cuando se cree/modifique una plataforma/categoría.

        /*
        $categories = Cache::remember('categories-' . $platform->slug, 60, function () use ($platform) {

            return $categories;
        });
        */

        return JsonHelper::success(
            [
                'categories' => $categories,
            ]
        );
    }



}
