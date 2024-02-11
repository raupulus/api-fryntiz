<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentCollection;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    /**
     * Devuelve un listado con todas las plataformas.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $platforms = Platform::select(['id', 'title', 'slug', 'description', 'domain', 'url_about', 'image_id'])->get();

        return \JsonHelper::success([
            'data' => [
                'platforms' => $platforms,
                'total' => $platforms->count(),
            ]
        ]);
    }

    /**
     * Devuelve toda la información acerca de una plataforma concreta.
     *
     * @param Platform $platform
     * @return JsonResponse
     */
    public function info(Platform $platform): JsonResponse
    {
        $version = '0.0.1';

        ## Tecnologías
        $technologies = [
            'vue',
            'tailwind',
            'laravel',
            'javascript',
        ];

        $contentTypes = ContentAvailableType::select(['id', 'plural_name', 'slug', 'name', 'description'])->get();

        ## Contenidos y páginas (Contador con cantidad total de contenidos por cada tipo de la plataforma)
        $contents = [
            'total' => $platform->contentsActive()->count(),
            'types' => $contentTypes->map(function($ele) use ($platform) {
                return $ele->getStatsByPlatform($platform);
            }),
        ];

        ## Páginas
        $pages = $platform->contentPages->map(function ($ele) {
            return [
                'title' => $ele->title,
                'slug' => $ele->slug,
                'excerpt' => $ele->excerpt,
                'url_image_small' => $ele->urlImageSmall,
                'url_image_medium' => $ele->urlImageMedium,
            ];
        });

        ## Autor de la plataforma, creador de esta plataforma de contenidos.
        $author = $platform->user?->basicInfo();

        return \JsonHelper::success([
            'data' => [
                'title' => $platform->title,
                'slug' => $platform->slug,
                'description' => $platform->description,
                'domain' => $platform->domain,
                'url_about' => $platform->url_about,
                'technologies' => $technologies,
                'contents' => $contents,
                'pages' => $pages,
                'author' => $author,
                'social_networks' => [
                    'youtube_channel_id' => $platform->youtube_channel_id,
                    'youtube_presentation_video_id' => $platform->youtube_presentation_video_id,
                    'twitter' => $platform->twitter,
                    'mastodon' => $platform->mastodon,
                    'twitch' => $platform->twitch,
                    'tiktok' => $platform->tiktok,
                    'instagram' => $platform->instagram,
                ],
            ]
        ]);
    }

    public function getContentByType(Request $request, Platform $platform, string $contentType)
    {
        $contentAvailableType = ContentAvailableType::where('slug', $contentType)->first();

        if (! $contentAvailableType) {
            return \JsonHelper::failed('!Tipo de Contenido no reconocido, un saludo!');
        }

        if (! $platform || !$platform->id) {
            return \JsonHelper::failed('!Tipo de Plataforma no reconocida, un saludo!');
        }

        $search = $request->get('search');
        $technology_id = $request->get('technology_id');
        $category_id = $request->get('category_id');
        $page = $request->get('page') ?? 1;
        $quantity = $request->get('quantity') ?? 10;

        $query = $platform->contentsActive()->select('contents.*')->where('type_id', $contentAvailableType->id);
        $total = (clone($query))->count();


        if ($technology_id) {
            $query->leftJoin('content_technologies', 'contents.id', 'content_technologies.content_id');
            //$query->groupBy('content_technologies.technology_id');
            //$query->groupBy('contents.id');
            $query->where('content_technologies.technology_id', $technology_id);
        }

        if ($category_id) {
            $query->leftJoin('content_categories', 'contents.id', 'content_categories.content_id');
            $query->leftJoin('platform_categories', 'content_categories.platform_category_id', 'platform_categories.id');
            $query->leftJoin('categories', 'platform_categories.category_id', 'categories.id');

            $query->where('categories.id', $category_id);
        }

        if ($search) {
            // TODO: Pensar si vamos a buscar también en etiquetas, tecnología, descripción y si lleva orden.
            $query->where(function ($q) use ($search) {
                return $q->orWhere('contents.title', 'iLike', '%' . $search . '%')
                    ->orWhere('contents.slug', 'iLike', '%' . $search . '%')
                    ->orWhere('contents.excerpt', 'iLike', '%' . $search . '%');
            });
        }

        // Límite debe ser menor a 50 contenidos.
        $quantity = ($quantity > 50) ? 50 : $quantity;

        $query->offset($quantity * ($page - 1))->limit($quantity);

        $contents = $query->get()->map(function ($ele) {
            return collect([
                'title' => $ele->title,
                'slug' => $ele->slug,
                'excerpt' => $ele->excerpt,
                'is_featured' => $ele->is_featured,
                'urlImageSmall' => $ele->urlImageSmall,
                'urlImageMedium' => $ele->urlImageMedium,
                'urlImageNormal' => $ele->urlImageNormal,
                'total_pages' => $ele->pages()->count(),
                'categories' => $ele->categoriesQuery()->pluck('name'),
                'tags' => $ele->tagsQuery()->pluck('name'),
                'metadata' => [
                    'web' => $ele->metadata?->web,
                    'telegram_channel' => $ele->metadata?->telegram_channel,
                    'youtube_channel' => $ele->metadata?->youtube_channel,
                    'youtube_video' => $ele->metadata?->youtube_video,
                    'gitlab' => $ele->metadata?->gitlab,
                    'github' => $ele->metadata?->github,
                    'mastodon' => $ele->metadata?->mastodon,
                    'twitter' => $ele->metadata?->twitter,
                ],
                'technologies' => $ele->technologies->map(function ($tech) {
                    return [
                        'name' => $tech->name,
                        'slug' => $tech->slug,
                    ];
                })

            ]);
        });

        ## Calculo total de páginas.
        $totalPages = (($total % $quantity) !== 0) ? (((int) ($total / $quantity)) + 1) : ($total % $quantity);

        return \JsonHelper::success([
            'totalElements' => $total, // Cantidad total de elementos
            'totalPages' => $totalPages, // Cantidad total de páginas
            'quantity_contents_current_page' => $contents->count(), // Cantidad de contenidos en la página actual
            'hasBackPage' => $page > 1,
            'hasNextPage' => $page < $totalPages,

            'search_params' => [
                'search' => $search,
                'page' => $page,
                'technology_id' => $technology_id,
                'category_id' => $category_id,
                'quantity' => $quantity,
                //'orderDirection' => 'DESC',
                //'orderBy' => 'updated_at',
            ],

            'contents' => $contents,
        ]);


    }
}

