<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentPageFormatEnum;
use App\Enums\GeminiAiModeEnum;
use App\Enums\GeminiThinkingLevelEnum;
use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Services\Content\GeminiContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * El stream SSE del asistente de Gemini en el editor de páginas.
 *
 * Eventos: `thought` y `content` mientras Gemini escribe; al terminar,
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
        GeminiContentService $gemini,
    ): StreamedResponse {
        $validated = $request->validate([
            'page_id' => ['nullable', 'integer'],
            'mode' => ['required', Rule::enum(GeminiAiModeEnum::class)],
            'thinking_level' => ['required', Rule::enum(GeminiThinkingLevelEnum::class)],
            'prompt' => ['nullable', 'string', 'max:5000'],
            // Una página larga ronda los 100 KB; esto es el techo de lo que se
            // manda a Google en una petición.
            'current_content' => ['nullable', 'string', 'max:500000'],
            'source_format' => ['required', Rule::enum(ContentPageFormatEnum::class)],
        ]);

        $page = null;
        if (! empty($validated['page_id'])) {
            $page = $content->pages()->whereKey((int) $validated['page_id'])->first();
            abort_unless($page !== null, 404, 'La página indicada no pertenece a este contenido.');
        }

        $mode = GeminiAiModeEnum::from((string) $validated['mode']);
        $format = ContentPageFormatEnum::from((string) $validated['source_format']);
        $thinkingLevel = GeminiThinkingLevelEnum::from((string) $validated['thinking_level']);

        $current = $mode === GeminiAiModeEnum::Create
            ? ['text' => '', 'placeholders' => []]
            : $gemini->prepareCurrentContent((string) ($validated['current_content'] ?? ''), $format);

        $systemInstruction = $gemini->buildSystemInstruction($mode, $content, $format);
        $fullPrompt = $gemini->buildContextPrompt($mode, $content, $page, (string) ($validated['prompt'] ?? ''), $current['text'], $format);

        return response()->stream(function () use ($gemini, $systemInstruction, $fullPrompt, $thinkingLevel, $mode, $format, $current): void {
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

            $gemini->streamGenerateContent($systemInstruction, $fullPrompt, $thinkingLevel, function (string $type, string $chunk) use ($send, &$text, &$warnings, &$error): void {
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
                $error = $warnings[0] ?? 'Gemini no ha devuelto ningún texto. Vuelve a intentarlo o cambia la instrucción.';
            }

            if ($error !== null) {
                $send('error', ['message' => $error]);

                return;
            }

            try {
                $result = $mode === GeminiAiModeEnum::Create
                    ? $gemini->parseCreatedPage($text, $format)
                    : $gemini->convertResultToFormat($text, $format, $mode, $current['placeholders']);
            } catch (Throwable $e) {
                Log::error('Gemini: no se ha podido adaptar el resultado: '.$e->getMessage(), ['exception' => $e]);
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
