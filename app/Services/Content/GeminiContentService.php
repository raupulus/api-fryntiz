<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\GeminiAiModeEnum;
use App\Enums\GeminiModelEnum;
use App\Enums\GeminiThinkingLevelEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * El asistente de Google Gemini del editor de páginas: crear una página,
 * editar o ampliar la actual y corregirla.
 *
 * A Gemini siempre se le habla en Markdown (o en HTML, si la página es HTML y
 * sólo se corrige), nunca con el JSON de Editor.js: el JSON es largo, se corta,
 * y cuando volvía con una llave de menos la página acababa siendo ese JSON
 * como texto. Lo que Markdown no sabe expresar (imágenes subidas, avisos,
 * vídeos, tarjetas de enlace…) viaja con el envoltorio `<div data-editorjs-block>`
 * del conversor, y a Gemini le llega sustituido por una marca fija
 * (`[[BLOQUE_1]]`) que no tiene que tocar: al volver se pone el bloque original
 * en su sitio, intacto, con su `file_id` y su pie de foto.
 */
class GeminiContentService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * Marca de un bloque que Gemini no ve. Al volver se admite también con las
     * barras de escape que pone a veces al escribir Markdown (`\[\[BLOQUE_1\]\]`).
     */
    private const PLACEHOLDER = '[[BLOQUE_%d]]';

    private const PLACEHOLDER_PATTERN = '/\\\\?\[\\\\?\[BLOQUE_(\d+)\\\\?\]\\\\?\]/';

    /**
     * Un envoltorio de `ContentFormatConverter::wrapBlock()`: la etiqueta de
     * apertura, el HTML del bloque y el cierre en su propia línea, sin líneas
     * en blanco dentro (en Markdown cortarían el bloque HTML).
     */
    private const WRAPPER_PATTERN = '/<div '.ContentFormatConverter::BLOCK_ATTRIBUTE.'="[A-Za-z0-9+\/=]+">(?:<\/div>|\n(?:(?!\n\n).)*?\n<\/div>(?=\n|$))/s';

    /**
     * Motivos de fin que no son «terminado»: la respuesta se ha cortado.
     */
    private const FINISH_REASONS = [
        'MAX_TOKENS' => 'la respuesta se ha cortado por larga',
        'SAFETY' => 'los filtros de seguridad de Google la han cortado',
        'RECITATION' => 'Google la ha cortado por parecerse demasiado a un texto publicado',
        'BLOCKLIST' => 'Google la ha cortado por contener términos bloqueados',
        'PROHIBITED_CONTENT' => 'Google la ha cortado por contenido prohibido',
        'SPII' => 'Google la ha cortado por contener datos personales sensibles',
    ];

    public function __construct(
        private readonly ContentFormatConverter $converter,
    ) {}

    /**
     * La clave de la API (ver `config/google.php`).
     *
     * @throws RuntimeException si no hay ninguna.
     */
    public function apiKey(): string
    {
        $key = (string) config('google.gemini.api_key', '');

        if (blank($key)) {
            throw new RuntimeException('No se ha configurado ninguna clave API para Google Gemini (GEMINI_API_KEY).');
        }

        return $key;
    }

    /**
     * Si hay clave: sin ella el botón del asistente no aparece.
     */
    public function isAvailable(): bool
    {
        return filled(config('google.gemini.api_key', ''));
    }

    public function model(): GeminiModelEnum
    {
        return GeminiModelEnum::default();
    }

    /**
     * Las instrucciones de sistema según el modo y el formato de la página.
     */
    public function buildSystemInstruction(
        GeminiAiModeEnum $mode,
        Content $content,
        ContentPageFormatEnum $format,
    ): string {
        $platform = $content->platform instanceof Platform ? $content->platform->title : 'General';

        $base = "Eres un asistente editorial experto que redacta y revisa contenidos para la plataforma digital de Raúl Caro Pastorino (@raupulus).\n".
            "La plataforma de publicación es '{$platform}' y el título general del contenido es '{$content->title}'.\n".
            "Tus textos deben ser profesionales, rigurosos, técnicos y pedagógicos. Usa siempre español con tildes y ortografía impecable.\n".
            "No inventes datos ni tecnologías inexistentes. Sé directo y evita preámbulos innecesarios o saludos.\n\n";

        $placeholders = "MARCAS DE BLOQUE: el texto puede llevar marcas como [[BLOQUE_1]]. Cada una sustituye a un bloque que no puedes ver ni editar (una imagen, un aviso, un vídeo, una tarjeta de enlace…).\n".
            "Cópialas tal cual, cada una sola en su propia línea. Puedes cambiarlas de sitio si reorganizas el texto, pero no las borres, no las modifiques y no las repitas.\n";

        return match ($mode) {
            GeminiAiModeEnum::Create => $base.
                "MODO: CREACIÓN DE NUEVA PÁGINA\n".
                "Debes proponer un título descriptivo y claro para la página, un slug limpio (minúsculas, separado por guiones y sin tildes) y redactar el contenido completo.\n".
                "ESTRUCTURA OBLIGATORIA AL INICIO:\n".
                "---\n".
                "title: Título de la página\n".
                "slug: slug-de-la-pagina\n".
                "---\n\n".
                "A continuación de ese bloque delimitado por '---', escribe el contenido íntegro en formato Markdown estructurado.\n".
                'Usa encabezados a partir de h3 (###), párrafos claros, listas o tablas según sea necesario.',

            GeminiAiModeEnum::Edit => $base.
                "MODO: EDICIÓN / AMPLIACIÓN DE PÁGINA ACTUAL\n".
                "Reescribe, amplía o adapta el texto según las instrucciones del usuario.\n".
                "Escribe directamente el contenido generado en formato Markdown estructurado (encabezados desde h3). No incluyas bloques '---' ni encabezados de título/slug.\n\n".
                $placeholders,

            GeminiAiModeEnum::Correct => $base.
                "MODO: SOLO CORREGIR ORTOGRAFÍA, ESTILO Y OPTIMIZAR\n".
                "Tu tarea es corregir tildes, puntuación, erratas y mejorar la fluidez y legibilidad del texto sin alterar el significado ni la intención original.\n".
                "Conserva intactos todos los enlaces URL, nombres de código y fragmentos técnicos.\n".
                ($format === ContentPageFormatEnum::Html
                    ? "Devuelve el código HTML corregido preservando intacta la estructura de etiquetas.\n\n"
                    : "Devuelve el texto corregido en Markdown, con la misma estructura que el original.\n\n").
                $placeholders,
        };
    }

    /**
     * El prompt del usuario con el contexto del contenido y de sus páginas.
     *
     * `$currentText` es la página ya preparada con `prepareCurrentContent()`.
     */
    public function buildContextPrompt(
        GeminiAiModeEnum $mode,
        Content $content,
        ?ContentPage $page,
        string $userPrompt,
        string $currentText = '',
        ?ContentPageFormatEnum $format = null,
    ): string {
        $platform = $content->platform instanceof Platform ? $content->platform->title : 'General';
        $context = "=== CONTEXTO DEL CONTENIDO ===\n".
            "Título general: {$content->title}\n".
            "Plataforma: {$platform}\n".
            'Extracto: '.($content->excerpt ?? '(Sin extracto)')."\n";

        // Índice de páginas hermanas existentes para no duplicar temáticas.
        $siblingPages = $content->pages()
            ->orderBy('order')
            ->select(['id', 'order', 'title', 'slug'])
            ->get();

        if ($siblingPages->isNotEmpty()) {
            $context .= "Otras páginas en este contenido:\n";
            foreach ($siblingPages as $sibling) {
                $isCurrent = $page !== null && (int) $sibling->id === (int) $page->id;
                $marker = $isCurrent ? ' (PÁGINA ACTUAL)' : '';
                $context .= "- Página {$sibling->order}: {$sibling->title} [slug: {$sibling->slug}]{$marker}\n";
            }
        }

        if ($page !== null) {
            $context .= "\nPágina actual siendo tratada: {$page->title} [slug: {$page->slug}]\n";
        }

        $context .= "\n=== INSTRUCCIÓN DEL USUARIO ===\n";
        $context .= filled($userPrompt) ? trim($userPrompt) : 'Procesa y optimiza el contenido según el modo indicado.';

        if ($mode !== GeminiAiModeEnum::Create && filled($currentText)) {
            $language = $format === ContentPageFormatEnum::Html ? 'HTML' : 'Markdown';
            $context .= "\n\n=== CONTENIDO ACTUAL DE LA PÁGINA (en {$language}) ===\n{$currentText}\n";
        }

        return $context;
    }

    /**
     * La página actual tal como se le manda a Gemini.
     *
     * Editor.js se pasa antes a Markdown. Los bloques sin equivalente se
     * sustituyen por marcas; `placeholders` guarda el envoltorio original de
     * cada una para `convertResultToFormat()`.
     *
     * @return array{text: string, placeholders: array<int, string>}
     */
    public function prepareCurrentContent(string $content, ContentPageFormatEnum $format): array
    {
        if (trim($content) === '') {
            return ['text' => '', 'placeholders' => []];
        }

        if ($format === ContentPageFormatEnum::EditorJs) {
            try {
                $content = $this->converter->convert($content, ContentPageFormatEnum::EditorJs, ContentPageFormatEnum::Markdown)->content;
            } catch (InvalidArgumentException|JsonException) {
                // El JSON que se está escribiendo a mano no vale todavía: no
                // hay página que mandar.
                return ['text' => '', 'placeholders' => []];
            }
        }

        $placeholders = [];
        $text = (string) preg_replace_callback(self::WRAPPER_PATTERN, function (array $match) use (&$placeholders): string {
            $index = count($placeholders) + 1;
            $placeholders[$index] = $match[0];

            return sprintf(self::PLACEHOLDER, $index);
        }, $content);

        return ['text' => trim($text), 'placeholders' => $placeholders];
    }

    /**
     * Pide a Gemini el texto en streaming y lo va pasando a `$callback`.
     *
     * `$callback($tipo, $texto)`: `thought` (resumen del razonamiento),
     * `content` (texto de la página), `warning` (la respuesta se ha cortado) y
     * `error` (no hay respuesta; después no llega nada más).
     *
     * La clave va en la cabecera `x-goog-api-key` y no en la URL: los errores
     * de conexión de Guzzle llevan la URL completa, y ese mensaje acababa en el
     * modal y en el log. Por lo mismo, al navegador sólo le llega un mensaje
     * genérico; el detalle va al log.
     *
     * @param  Closure(string $type, string $text): void  $callback
     */
    public function streamGenerateContent(
        string $systemInstruction,
        string $userPrompt,
        GeminiThinkingLevelEnum $thinkingLevel,
        Closure $callback,
    ): void {
        $apiKey = $this->apiKey();
        $model = $this->model();

        // Sin `temperature`: los modelos 3.x lo ignoran y Google pide quitarlo.
        // `includeThoughts` es lo que hace que lleguen los resúmenes del
        // razonamiento; sin él, el modal se quedaba mudo mientras Gemini piensa.
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'generationConfig' => [
                'thinkingConfig' => [
                    'thinkingLevel' => $thinkingLevel->value,
                    'includeThoughts' => true,
                ],
            ],
        ];

        try {
            $response = Http::withOptions(['stream' => true, 'timeout' => 180, 'connect_timeout' => 15])
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post(self::API_BASE."/models/{$model->value}:streamGenerateContent?alt=sse", $payload);

            if ($response->failed()) {
                $message = (string) ($response->json('error.message') ?? Str::limit(strip_tags($response->body()), 300));

                Log::warning('Gemini: error de la API', ['status' => $response->status(), 'error' => $message]);
                $callback('error', $this->apiErrorMessage($response->status(), $message, $model));

                return;
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';

            while (! $body->eof()) {
                if (connection_aborted()) {
                    return;
                }

                $buffer .= str_replace("\r\n", "\n", $body->read(1024));

                while (($position = strpos($buffer, "\n\n")) !== false) {
                    $this->processSseBlock(substr($buffer, 0, $position), $callback);
                    $buffer = substr($buffer, $position + 2);
                }
            }

            if (trim($buffer) !== '' && ! connection_aborted()) {
                $this->processSseBlock($buffer, $callback);
            }
        } catch (Throwable $e) {
            Log::error('Gemini: fallo de conexión: '.$e->getMessage(), ['exception' => $e]);
            $callback('error', 'No se ha podido conectar con Gemini. Vuelve a intentarlo en un momento.');
        }
    }

    /**
     * Lo que se le dice a quien redacta cuando Google responde con un error.
     */
    private function apiErrorMessage(int $status, string $message, GeminiModelEnum $model): string
    {
        return match (true) {
            // Google lo comprueba antes que la propia petición.
            $status === 402 => 'La cuenta de Gemini no tiene saldo. Recárgalo en Google AI Studio (https://ai.studio/projects) y vuelve a intentarlo.',
            $status === 429 => 'Se ha agotado la cuota de Gemini de esta clave. Espera un poco y vuelve a intentarlo.',
            in_array($status, [401, 403], true) => 'La clave de Gemini no vale o no tiene permiso para usar la API ('.$message.').',
            $status === 404 => 'El modelo '.$model->label().' no está disponible para esta clave ('.$message.').',
            $status >= 500 => 'Gemini no está disponible ahora mismo. Vuelve a intentarlo en un momento.',
            default => 'Gemini ha rechazado la petición ('.$status.'): '.$message,
        };
    }

    /**
     * Un evento SSE de Gemini.
     *
     * @param  Closure(string $type, string $text): void  $callback
     */
    private function processSseBlock(string $block, Closure $callback): void
    {
        foreach (explode("\n", trim($block)) as $line) {
            $line = trim($line);

            if (! str_starts_with($line, 'data: ')) {
                continue;
            }

            try {
                $data = json_decode(substr($line, 6), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (! is_array($data)) {
                continue;
            }

            // La petición entera bloqueada: no llega ningún candidato.
            if (filled($data['promptFeedback']['blockReason'] ?? null)) {
                $callback('error', 'Gemini ha bloqueado la petición ('.$data['promptFeedback']['blockReason'].'). Cambia la instrucción o el texto y vuelve a intentarlo.');

                return;
            }

            $candidate = $data['candidates'][0] ?? null;

            if (! is_array($candidate)) {
                continue;
            }

            foreach ($candidate['content']['parts'] ?? [] as $part) {
                $text = is_array($part) ? (string) ($part['text'] ?? '') : '';

                if ($text === '') {
                    continue;
                }

                $callback(($part['thought'] ?? false) === true ? 'thought' : 'content', $text);
            }

            $reason = (string) ($candidate['finishReason'] ?? '');

            if (isset(self::FINISH_REASONS[$reason])) {
                $callback('warning', 'Gemini no ha terminado: '.self::FINISH_REASONS[$reason].'. Revisa el final antes de aplicarlo.');
            } elseif (! in_array($reason, ['', 'STOP', 'FINISH_REASON_UNSPECIFIED'], true)) {
                $callback('warning', 'Gemini no ha terminado ('.$reason.'). Revisa el resultado antes de aplicarlo.');
            }
        }
    }

    /**
     * Título, slug y contenido de una página creada.
     *
     * @return array{title: string, slug: string, content: string, warnings: list<string>}
     */
    public function parseCreatedPage(string $generatedText, ContentPageFormatEnum $targetFormat): array
    {
        $title = 'Nueva página';
        $slug = 'nueva-pagina';
        $text = $this->withoutFence(str_replace("\r\n", "\n", trim($generatedText)));

        $body = $text;

        // Extraer frontmatter si existe: --- title: ... slug: ... ---
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $text, $matches)) {
            $metaBlock = $matches[1];
            $body = trim($matches[2]);

            if (preg_match('/^title:\s*(.+)$/m', $metaBlock, $titleMatch)) {
                $title = trim(trim($titleMatch[1]), " \"'");
            }
            if (preg_match('/^slug:\s*(.+)$/m', $metaBlock, $slugMatch)) {
                $slug = Str::slug(trim(trim($slugMatch[1]), " \"'"));
            }
        } elseif (preg_match('/^#+\s+(.+)$/m', $text, $titleMatch)) {
            // Sin frontmatter, el primer título hace de título de la página.
            $title = trim($titleMatch[1]);
            $slug = Str::slug($title);
            $body = trim(preg_replace('/^#+\s+.+$/m', '', $text, 1) ?? $text);
        }

        $conversion = $this->converter->convert($body, ContentPageFormatEnum::Markdown, $targetFormat);

        return [
            'title' => $title,
            'slug' => $slug,
            'content' => $conversion->content,
            'warnings' => $conversion->warnings,
        ];
    }

    /**
     * El resultado de «Editar» o «Corregir» en el formato de la página.
     *
     * Gemini responde en Markdown, salvo al corregir una página HTML, que
     * responde en HTML. Antes de convertir se ponen en su sitio los bloques
     * de las marcas, y se avisa de los que Gemini haya quitado.
     *
     * @param  array<int, string>  $placeholders  Los de `prepareCurrentContent()`.
     * @return array{content: string, warnings: list<string>}
     */
    public function convertResultToFormat(
        string $generatedText,
        ContentPageFormatEnum $targetFormat,
        GeminiAiModeEnum $mode,
        array $placeholders = [],
    ): array {
        $answersInHtml = $mode === GeminiAiModeEnum::Correct && $targetFormat === ContentPageFormatEnum::Html;
        $text = $this->withoutFence(str_replace("\r\n", "\n", trim($generatedText)));

        [$text, $warnings] = $this->restorePlaceholders($text, $placeholders, $answersInHtml);

        if ($answersInHtml || $targetFormat === ContentPageFormatEnum::Markdown) {
            return ['content' => $text, 'warnings' => $warnings];
        }

        $conversion = $this->converter->convert($text, ContentPageFormatEnum::Markdown, $targetFormat);

        return [
            'content' => $conversion->content,
            'warnings' => [...$warnings, ...$conversion->warnings],
        ];
    }

    /**
     * Cada marca vuelve a ser su bloque. En Markdown el bloque va separado por
     * líneas en blanco, que es como se reconoce un bloque HTML; en HTML, en
     * lugar del párrafo en el que Gemini lo haya dejado.
     *
     * @param  array<int, string>  $placeholders
     * @return array{0: string, 1: list<string>}
     */
    private function restorePlaceholders(string $text, array $placeholders, bool $html): array
    {
        $used = [];

        $restore = function (array $match) use ($placeholders, $html, &$used): string {
            $index = (int) $match[1];

            if (! isset($placeholders[$index])) {
                return '';
            }

            $used[$index] = true;

            return $html ? $placeholders[$index] : "\n\n".$placeholders[$index]."\n\n";
        };

        if ($html) {
            $pattern = substr(self::PLACEHOLDER_PATTERN, 1, -1);
            $text = (string) preg_replace_callback('/<p>\s*'.$pattern.'\s*<\/p>/', $restore, $text);
        }

        $text = (string) preg_replace_callback(self::PLACEHOLDER_PATTERN, $restore, $text);
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", $text));

        $missing = array_diff_key($placeholders, $used);

        if ($missing === []) {
            return [$text, []];
        }

        $labels = collect($missing)
            ->map(fn (string $wrapper): string => $this->converter->blockLabel((string) ($this->converter->wrappedBlock($wrapper)['block']['type'] ?? '')))
            ->countBy()
            ->map(fn (int $count, string $label): string => $label.($count > 1 ? ' ×'.$count : ''))
            ->implode(', ');

        return [$text, [sprintf(
            'Gemini ha quitado %s que no podía editar (%s). No están en el resultado: si los quieres, no lo apliques o vuelve a añadirlos.',
            count($missing) === 1 ? '1 bloque' : count($missing).' bloques',
            $labels,
        )]];
    }

    /**
     * Sin la valla de código con la que a veces envuelve la respuesta entera.
     */
    private function withoutFence(string $text): string
    {
        return preg_match('/^```[a-z]*\n(.*)\n```$/s', $text, $match) ? trim($match[1]) : $text;
    }
}
