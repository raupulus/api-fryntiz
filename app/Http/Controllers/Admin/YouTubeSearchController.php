<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\YouTube\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador administrativo para la búsqueda de vídeos de YouTube.
 *
 * Expone un endpoint seguro y con control de cuota para el componente
 * `YoutubeVideoField` de Filament, eliminando la necesidad de exponer la API Key
 * en el navegador.
 */
class YouTubeSearchController extends Controller
{
    public function __construct(
        private readonly YouTubeService $youTubeService,
    ) {}

    /**
     * Busca vídeos en YouTube mediante el servicio intermediario del backend.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'channel_id' => ['nullable', 'string', 'max:64'],
            'page_token' => ['nullable', 'string', 'max:64'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = (string) ($validated['q'] ?? '');
        $channelId = isset($validated['channel_id']) ? (string) $validated['channel_id'] : null;
        $pageToken = isset($validated['page_token']) ? (string) $validated['page_token'] : null;
        $maxResults = isset($validated['max_results']) ? (int) $validated['max_results'] : 10;

        $result = $this->youTubeService->search(
            query: $query,
            channelId: $channelId,
            pageToken: $pageToken,
            maxResults: $maxResults,
        );

        if (isset($result['error'])) {
            $statusCode = is_numeric($result['error']['code'] ?? null)
                ? (int) $result['error']['code']
                : 400;

            // Asegurar que el status code sea válido para HTTP
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 502;
            }

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }
}
