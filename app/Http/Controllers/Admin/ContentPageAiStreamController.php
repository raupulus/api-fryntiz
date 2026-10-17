<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AiModeEnum;
use App\Enums\AiReasoningLevelEnum;
use App\Enums\ContentPageFormatEnum;
use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Services\Content\ContentAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * El stream SSE del asistente de IA en el editor de páginas, con el
 * proveedor elegido en el modal.
 *
 * Eventos: `thought` y `content` mientras la IA escribe; al terminar,
 * `complete` con el resultado ya en el formato de la página y `done`. Si algo
 * falla, sólo `error`, y no llega nada más: con un `complete` vacío detrás, el
 * modal habría dado por buena una respuesta que no existe.
 *
 * Va con su propio límite (`content-ai`, ver `routes/web.php`).
 */
class ContentPageAiStreamController extends Controller
{
    public function stream(
        Request $request,
        Content $content,
        ContentAiService $assistant,
    ): StreamedResponse {
        $providers = AiProvider::usable();

        $validated = $request->validate([
            'page_id' => ['nullable', 'integer'],
            'mode' => ['required', Rule::enum(AiModeEnum::class)],
            // Sólo los que salen en el modal: activos y con una clave legible.
            'provider' => ['required', 'string', Rule::in($providers->map(fn (AiProvider $provider): string => $provider->provider->value)->all())],
            'reasoning_level' => ['nullable', Rule::enum(AiReasoningLevelEnum::class)],
            // Cabe una noticia o una guía pegada para reescribirla.
            'prompt' => ['nullable', 'string', 'max:50000'],
            // Una página larga ronda los 100 KB; esto es el techo de lo que se
            // manda al proveedor en una petición.
            'current_content' => ['nullable', 'string', 'max:500000'],
            'source_format' => ['required', Rule::enum(ContentPageFormatEnum::class)],
        ]);

        $page = null;
        if (! empty($validated['page_id'])) {
            $page = $content->pages()->whereKey((int) $validated['page_id'])->first();
            abort_unless($page !== null, 404, 'La página indicada no pertenece a este contenido.');
        }

        $mode = AiModeEnum::from((string) $validated['mode']);
        $format = ContentPageFormatEnum::from((string) $validated['source_format']);
        $provider = $providers->firstOrFail(fn (AiProvider $provider): bool => $provider->provider->value === $validated['provider']);
        $level = AiReasoningLevelEnum::tryFrom((string) ($validated['reasoning_level'] ?? '')) ?? $provider->provider->defaultReasoningLevel();

        // Cada proveedor admite los suyos: Mistral sólo razonar o no.
        if (! in_array($level, $provider->provider->reasoningLevels(), true)) {
            throw ValidationException::withMessages([
                'reasoning_level' => 'Ese nivel de razonamiento no vale para '.$provider->provider->label().'.',
            ]);
        }

        $current = $mode === AiModeEnum::Create
            ? ['text' => '', 'placeholders' => []]
            : $assistant->prepareCurrentContent((string) ($validated['current_content'] ?? ''), $format);

        $systemInstruction = $assistant->buildSystemInstruction($mode, $content, $format);
        $fullPrompt = $assistant->buildContextPrompt($mode, $content, $page, (string) ($validated['prompt'] ?? ''), $current['text'], $format);

        $pageTitle = $page?->title;

        return response()->stream(function () use ($assistant, $provider, $systemInstruction, $fullPrompt, $level, $mode, $format, $current, $pageTitle): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(200);
            }

            // Sin búferes de salida: cada trozo tiene que salir en cuanto llega.
            if (! app()->runningUnitTests()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }

            $send = function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                if (! app()->runningUnitTests() && ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $text = '';
            $warnings = [];
            $error = null;

            $assistant->streamGenerateContent($provider, $systemInstruction, $fullPrompt, $level, function (string $type, string $chunk) use ($send, &$text, &$warnings, &$error): void {
                if ($type === 'thought') {
                    $send('thought', ['text' => $chunk]);
                } elseif ($type === 'content') {
                    $text .= $chunk;
                    $send('content', ['text' => $chunk]);
                } elseif ($type === 'warning') {
                    $warnings[] = $chunk;
                } elseif ($type === 'error') {
                    $error = $chunk;
                }
            });

            if ($error === null && trim($text) === '') {
                $error = $warnings[0] ?? $provider->provider->label().' no ha devuelto ningún texto. Vuelve a intentarlo o cambia la instrucción.';
            }

            if ($error !== null) {
                $send('error', ['message' => $error]);

                return;
            }

            try {
                $result = $mode === AiModeEnum::Create
                    ? $assistant->parseCreatedPage($text, $format)
                    : $assistant->convertResultToFormat($text, $format, $mode, $current['placeholders'], $pageTitle);
            } catch (Throwable $e) {
                Log::error('Asistente IA: no se ha podido adaptar el resultado: '.$e->getMessage(), ['exception' => $e]);
                $send('error', ['message' => 'No se ha podido adaptar el resultado al formato de la página.']);

                return;
            }

            $send('complete', [
                'mode' => $mode->value,
                'title' => $result['title'] ?? null,
                'slug' => $result['slug'] ?? null,
                'content' => $result['content'],
                'raw_markdown' => $text,
                'warnings' => array_values(array_unique([...$warnings, ...$result['warnings']])),
            ]);

            $send('done', ['status' => 'finished']);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
