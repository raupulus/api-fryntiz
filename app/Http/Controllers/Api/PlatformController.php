<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\Technology;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $technologies = Technology::select(['technologies.name', 'technologies.slug', 'technologies.image_id'])
            //->leftJoin('technologies', 'content_technologies.technology_id', 'technologies.id')
            ->leftJoin('content_technologies', 'technologies.id', 'content_technologies.technology_id')
            ->leftJoin('contents', 'content_technologies.content_id', 'contents.id')
            ->leftJoin('platforms', 'contents.platform_id', 'platforms.id')
            ->where('platforms.id', $platform->id)
            ->where('contents.type_id', 5) // Tener en cuenta que limito solo a proyectos????
            ->whereNotNull('technologies.name')
            ->whereNotNull('technologies.slug')
            ->groupBy('technologies.slug')
            ->groupBy('technologies.name')
            ->groupBy('technologies.image_id')
            ->get()
        ;

        $technologies = $technologies->map(function ($ele) {
            return [
                'name' => $ele->name,
                'slug' => $ele->slug,
                'urlImageSmall' => $ele->urlImageSmall
            ];
        });

        $contentTypes = ContentAvailableType::select(['id', 'plural_name', 'slug', 'name', 'description'])->get();

        ## Contenidos y páginas (Contador con cantidad total de contenidos por cada tipo de la plataforma)
        $contents = [
            'total' => $platform->contentsActive()->count(),
            'types' => $contentTypes->map(function ($ele) use ($platform) {
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

        if (!$contentAvailableType) {
            return \JsonHelper::failed('!Tipo de Contenido no reconocido, un saludo!');
        }

        if (!$platform->id) {
            return \JsonHelper::failed('!Tipo de Plataforma no reconocida, un saludo!');
        }

        $search = $request->get('search');
        $technology = $request->get('technology');
        $technology_id = $request->get('technology_id');
        $category = $request->get('category');
        $category_id = $request->get('category_id');
        $page = $request->get('page') ?? 1;
        $quantity = $request->get('quantity') ?? 10;


        //$platform->contentsActive()

        $query = Content::select('contents.*')
            ->where('type_id', $contentAvailableType->id)
            ->where('platform_id', $platform->id)
            ->where('contents.is_active', true)
            ->whereNotNull('contents.published_at');


        if ($search || $technology || $technology_id) {
            $query->leftJoin('content_technologies', 'contents.id', 'content_technologies.content_id');
            $query->leftJoin('technologies', 'content_technologies.technology_id', 'technologies.id');

            $query->groupBy('content_technologies.content_id');
        }

        if ($technology) {
            $query->where('technologies.slug', $technology);
        } elseif ($technology_id) {
            $query->where('technologies.id', $technology_id);
        }

        if ($category || $category_id) {
            $query->leftJoin('content_categories', 'contents.id', 'content_categories.content_id');
            $query->leftJoin('platform_categories', 'content_categories.platform_category_id', 'platform_categories.id');
            $query->leftJoin('categories', 'platform_categories.category_id', 'categories.id');

            if ($category) {
                $query->where('categories.slug', $category);
            } elseif ($category_id) {
                $query->where('categories.id', $category_id);
            }
        }

        if ($search) {
            // TODO: Pensar si vamos a buscar también en etiquetas, tecnología, descripción y si lleva orden.
            $query->where(function ($q) use ($search) {
                return $q->orWhere('contents.title', 'iLike', '%' . $search . '%')
                    ->orWhere('contents.slug', 'iLike', '%' . $search . '%')
                    ->orWhere('contents.excerpt', 'iLike', '%' . $search . '%')
                    ->orWhere('technologies.slug', 'iLike', '%' . $search . '%');
            });
        }

        $query->groupBy('contents.id');

        $totalQuery = (clone($query));

        $total = $totalQuery->select([
            DB::raw('COUNT(*) as total'),
        ])
            ->get()->count();


        // Límite debe ser menor a 50 contenidos.
        $quantity = ($quantity > 50) ? 50 : $quantity;

        $query->offset($quantity * ($page - 1))->limit($quantity);

        Carbon::setLocale(config('app.locale'));

        $contents = $query->get()->map(function ($ele) {
            return collect([
                'title' => $ele->title,
                'slug' => $ele->slug,
                'excerpt' => $ele->excerpt,
                'is_featured' => $ele->is_featured,
                'urlImageSmall' => $ele->urlImageSmall,
                'urlImageMedium' => $ele->urlImageMedium,
                'urlImage' => $ele->urlImageNormal,
                'created_at' => $ele->created_at,
                'created_at_human' => $ele->created_at->translatedFormat('d F Y'),
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
                        'urlImageSmall' => $tech->urlImageSmall,
                    ];
                })

            ]);
        });

        ## Calculo total de páginas.
        if ($total === 0) {
            $totalPages = 0;
        } elseif ($total <= $quantity) { // Se piden más elementos por página de los que hay.
            $totalPages = 1;
        } else {
            $totalPages = (($total % $quantity) !== 0) ? (((int)($total / $quantity)) + 1) : ($total / $quantity);
        }


        return \JsonHelper::success([
            'pagination' => [
                'totalElements' => $total, // Cantidad total de elementos
                'totalPages' => $totalPages, // Cantidad total de páginas
                'quantity_contents_current_page' => $contents->count(), // Cantidad de contenidos en la página actual
                'hasBackPage' => $page > 1,
                'hasNextPage' => $page < $totalPages,
                'currentPage' => $page,
            ],
            'search_params' => [
                'search' => $search,
                'page' => $page,
                'technology' => $technology,
                'technology_id' => $technology_id,
                'category' => $category,
                'category_id' => $category_id,
                'quantity' => $quantity,
                'orderDirection' => 'desc',
                'orderBy' => ['is_featured', 'updated_at'],
            ],

            'contents' => $contents,
        ]);


    }
}

