<?php

declare(strict_types=1);

namespace App\Services\YouTube;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio centralizado para la interacción con la API de YouTube Data v3.
 *
 * Aísla las llamadas HTTP a Google en el backend, gestiona la clave de API privada
 * y aplica una capa de caché para evitar el agotamiento de la cuota diaria (100 unidades
 * por petición de búsqueda sobre un límite estándar de 10.000).
 */
class YouTubeService
{
    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    private const CACHE_TTL_SECONDS = 1800; // 30 minutos

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string) config('google.api_key', '');
    }

    /**
     * Busca vídeos en YouTube, opcionalmente restringidos a un canal concreto.
     *
     * @param  string  $query  Texto de búsqueda libre.
     * @param  string|null  $channelId  ID del canal de YouTube (ej. UC...).
     * @param  string|null  $pageToken  Token para paginación de resultados.
     * @param  int  $maxResults  Cantidad de resultados por página (máx. 50).
     * @return array<string, mixed>
     */
    public function search(
        string $query = '',
        ?string $channelId = null,
        ?string $pageToken = null,
        int $maxResults = 10,
    ): array {
        if (blank($this->apiKey)) {
            return [
                'error' => [
                    'message' => 'La clave de API de YouTube no está configurada en el servidor (GOOGLE_API_KEY).',
                    'code' => 500,
                ],
            ];
        }

        $sanitizedQuery = trim($query);
        $maxResults = max(1, min($maxResults, 50));

        $cacheKey = 'yt_search_'.md5(
            ($channelId ?? 'all').'_'.
            $sanitizedQuery.'_'.
            ($pageToken ?? 'first').'_'.
            $maxResults
        );

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($sanitizedQuery, $channelId, $pageToken, $maxResults): array {
            try {
                $params = [
                    'key' => $this->apiKey,
                    'part' => 'id,snippet',
                    'type' => 'video',
                    'maxResults' => $maxResults,
                    'order' => 'relevance',
                    'safeSearch' => 'none',
                ];

                if (filled($sanitizedQuery)) {
                    $params['q'] = $sanitizedQuery;
                }

                if (filled($channelId)) {
                    $params['channelId'] = $channelId;
                }

                if (filled($pageToken)) {
                    $params['pageToken'] = $pageToken;
                }

                $response = Http::timeout(6)
                    ->acceptJson()
                    ->get(self::BASE_URL.'/search', $params);

                if (! $response->successful()) {
                    $data = $response->json();
                    $errorMessage = $data['error']['message'] ?? 'Error desconocido de la API de YouTube (HTTP '.$response->status().').';

                    Log::warning('YouTube API search error', [
                        'status' => $response->status(),
                        'message' => $errorMessage,
                        'channel_id' => $channelId,
                        'query' => $sanitizedQuery,
                    ]);

                    return [
                        'error' => [
                            'message' => $errorMessage,
                            'code' => $response->status(),
                        ],
                    ];
                }

                /** @var array<string, mixed> $result */
                $result = $response->json();

                return $result;
            } catch (Throwable $e) {
                Log::error('YouTube API connection exception', [
                    'message' => $e->getMessage(),
                    'channel_id' => $channelId,
                    'query' => $sanitizedQuery,
                ]);

                return [
                    'error' => [
                        'message' => 'No se ha podido conectar con el servicio de YouTube en este momento.',
                        'code' => 503,
                    ],
                ];
            }
        });
    }

    /**
     * Obtiene los datos detallados de un vídeo por su ID (consumo: 1 unidad de cuota).
     *
     * @return array<string, mixed>|null
     */
    public function getVideo(string $videoId): ?array
    {
        if (blank($this->apiKey) || blank($videoId)) {
            return null;
        }

        $cacheKey = 'yt_video_'.trim($videoId);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS * 2, function () use ($videoId): ?array {
            try {
                $response = Http::timeout(5)
                    ->acceptJson()
                    ->get(self::BASE_URL.'/videos', [
                        'key' => $this->apiKey,
                        'part' => 'id,snippet,contentDetails',
                        'id' => trim($videoId),
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                $items = $data['items'] ?? [];

                return ! empty($items) ? $items[0] : null;
            } catch (Throwable $e) {
                Log::warning('YouTube API getVideo exception: '.$e->getMessage());

                return null;
            }
        });
    }
}
