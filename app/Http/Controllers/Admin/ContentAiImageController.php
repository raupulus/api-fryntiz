<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Services\Ai\AiImageException;
use App\Services\Content\ContentAiImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Portadas con IA (la de la entrada o la de una página): generar de 1 a 5, o
 * mejorar la que hay. Responde en SSE: `image` por cada una (su vista previa;
 * ver `ContentAiImageService`), y `done`; si no sale ninguna, sólo `error`.
 *
 * Una imagen puede tardar hasta un par de minutos, y nginx corta una
 * respuesta que pasa 60 s sin mandar nada: mientras se espera, sale un
 * comentario SSE (`: ping`) cada pocos segundos (D53).
 *
 * Va con el mismo límite que el asistente de texto (`content-ai`).
 */
class ContentAiImageController extends Controller
{
    private const PING_SECONDS = 5;

    public function stream(Request $request, Content $content, ContentAiImageService $images): StreamedResponse
    {
        $providers = AiProvider::usableForImages();

        $validated = $request->validate([
            'mode' => ['required', Rule::in(['generate', 'improve'])],
            // Sólo los que salen en el modal: usables y con modelo de imágenes.
            'provider' => ['required', 'string', Rule::in($providers->map(fn (AiProvider $provider): string => $provider->provider->value)->all())],
            'count' => ['required', 'integer', 'min:1', 'max:'.ContentAiImageService::MAX_IMAGES],
            'quality' => ['nullable', 'string'],
            'use_colors' => ['boolean'],
            'page_id' => ['nullable', 'integer'],
            'prompt' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'required_if:mode,improve'],
        ]);

        $provider = $providers->firstOrFail(fn (AiProvider $provider): bool => $provider->provider->value === $validated['provider']);
        $quality = $validated['quality'] ?? null;
        $qualities = $provider->provider->imageQualities();

        if ($quality !== null && ! array_key_exists($quality, $qualities)) {
            throw ValidationException::withMessages(['quality' => 'Esa calidad no vale para '.$provider->provider->label().'.']);
        }

        $page = null;
        if (! empty($validated['page_id'])) {
            $page = $content->pages()->whereKey((int) $validated['page_id'])->first();
            abort_unless($page !== null, 404, 'La página indicada no pertenece a este contenido.');
        }

        $improve = $validated['mode'] === 'improve';
        $source = null;

        if ($improve) {
            if (! $provider->provider->supportsImageEditing()) {
                throw ValidationException::withMessages(['provider' => $provider->provider->label().' no sabe mejorar una imagen: sólo las genera.']);
            }

            $source = $images->source((string) $validated['source'], $content, (int) $request->user()->getKey());

            if ($source === null) {
                throw ValidationException::withMessages(['source' => 'No se encuentra la imagen que se iba a mejorar. Cierra la ventana y vuelve a abrirla.']);
            }
        }

        $prompt = $images->buildPrompt($content, $page?->title, (string) ($validated['prompt'] ?? ''), (bool) ($validated['use_colors'] ?? false), $improve);
        $count = (int) $validated['count'];

        return response()->stream(function () use ($images, $provider, $prompt, $source, $count, $quality): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(300);
            }

            if (! app()->runningUnitTests()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }

            $flush = function (): void {
                if (! app()->runningUnitTests() && ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $send = function (string $event, array $data) use ($flush): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
                $flush();
            };

            $lastPing = microtime(true);
            $tick = function () use (&$lastPing, $flush): void {
                if (microtime(true) - $lastPing >= self::PING_SECONDS) {
                    $lastPing = microtime(true);
                    echo ": ping\n\n";
                    $flush();
                }
            };

            // Para que el navegador sepa enseguida que ha empezado.
            echo ": inicio\n\n";
            $flush();

            try {
                $generated = $source === null
                    ? $provider->imageConnector()->generate($provider, $prompt, $count, $quality, $tick)
                    : $provider->imageConnector()->edit($provider, $source, $prompt, $count, $quality, $tick);
            } catch (AiImageException $e) {
                $send('error', ['message' => $e->getMessage()]);

                return;
            } catch (Throwable $e) {
                Log::error('Portadas con IA: '.$e->getMessage(), ['exception' => $e]);
                $send('error', ['message' => 'No se ha podido generar la imagen. Vuelve a intentarlo.']);

                return;
            }

            foreach ($generated as $image) {
                $send('image', $images->storeCandidate($images->toCover($image)));
            }

            $send('done', ['count' => count($generated)]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
