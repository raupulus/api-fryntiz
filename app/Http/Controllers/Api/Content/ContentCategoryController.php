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
    /**
     * Retrieves the list of categories along with their associated subcategories for a given platform.
     * The data is structured without unnecessary attributes and formatted for the response.
     *
     * @param Request $request The HTTP request instance.
     * @param Platform $platform The platform entity for which categories are being fetched.
     * @return JsonResponse A JSON response containing the formatted categories data.
     */
    public function index(Request $request, Platform $platform): JsonResponse
    {
        $categories = $platform->getApiCategories();

        return JsonHelper::success(
            [
                'categories' => $categories,
            ]
        );
    }



}
