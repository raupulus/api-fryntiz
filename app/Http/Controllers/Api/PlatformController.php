<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $text = $request->get('text');
        $technology = $request->get('technology');
        $page = $request->get('page') ?? 1;
        $limit = $request->get('limit') ?? 10;

        //$query = $platform->contents()->paginate()



        if ($technology) {
            // Buscar modelo, filtrar por su ID
            // Plantear si vamos a recibir un slug o un id
        }



        // Límite es menor de 50
        $limit = ($limit > 50) ? 50 : $limit;




        return \JsonHelper::success([
            'message' => 'Si puedes leer esto bien',
            'platform' => $platform,
            'contentType' => $contentType,
        ]);


    }
}

