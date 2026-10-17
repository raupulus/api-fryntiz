<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\AiModeEnum;
use App\Enums\AiReasoningLevelEnum;
use App\Enums\ContentPageFormatEnum;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use App\Models\PlatformEditorProfile;
use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

/**
 * El asistente de IA del editor de páginas: crear una página, editar o
 * ampliar la actual y corregirla, con el proveedor que se elija en el modal
 * (`AiProvider`; la petición la hace su conector, `App\Services\Ai`).
 *
 * A la IA siempre se le habla en Markdown (o en HTML, si la página es HTML y
 * sólo se corrige), nunca con el JSON de Editor.js: el JSON es largo, se corta,
 * y cuando volvía con una llave de menos la página acababa siendo ese JSON
 * como texto. Lo que Markdown no sabe expresar (imágenes subidas, avisos,
 * vídeos, tarjetas de enlace…) viaja con el envoltorio `<div data-editorjs-block>`
 * del conversor, y a la IA le llega sustituido por una marca fija
 * (`[[BLOQUE_1]]`) que no tiene que tocar: al volver se pone el bloque original
 * en su sitio, intacto, con su `file_id` y su pie de foto.
 */
class ContentAiService
{
    /**
     * La instrucción editorial cuando ni la entrada ni su plataforma tienen
     * una propia.
     */
    public const DEFAULT_EDITORIAL_INSTRUCTION = "Eres un asistente editorial experto que redacta y revisa contenidos para la plataforma digital de Raúl Caro Pastorino (@raupulus).\n".
        "Tus textos deben ser profesionales, rigurosos, técnicos y pedagógicos. Usa siempre español con tildes y ortografía impecable.\n".
        'No inventes datos ni tecnologías inexistentes. Sé directo y evita preámbulos innecesarios o saludos.';

    /**
     * Marca de un bloque que la IA no ve. Al volver se admite también con las
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

    public function __construct(
        private readonly ContentFormatConverter $converter,
    ) {}

    /**
     * Si hay algún proveedor activo con clave: sin él, el botón del asistente
     * no aparece.
     */
    public function isAvailable(): bool
    {
        return AiProvider::usable()->isNotEmpty();
    }

    /**
     * Las instrucciones de sistema: la editorial (quién escribe y cómo), el
     * contexto y, al final, las reglas técnicas del modo.
     *
     * Las reglas técnicas no se pueden cambiar desde el panel: el resultado se
     * lee con ellas (el bloque `---` con título y slug al crear, las marcas de
     * bloque al editar o corregir). Por eso van detrás de la instrucción
     * editorial, y ninguna instrucción del panel las quita.
     */
    public function buildSystemInstruction(
        AiModeEnum $mode,
        Content $content,
        ContentPageFormatEnum $format,
    ): string {
        $platform = $content->platform instanceof Platform ? $content->platform->title : 'General';

        $base = trim($this->editorialInstruction($content))."\n\n".
            "La plataforma de publicación es '{$platform}' y el título general del contenido es '{$content->title}'.\n\n";

        $placeholders = "MARCAS DE BLOQUE: el texto puede llevar marcas como [[BLOQUE_1]]. Cada una sustituye a un bloque que no puedes ver ni editar (una imagen, un aviso, un vídeo, una tarjeta de enlace…).\n".
            "Cópialas tal cual, cada una sola en su propia línea. Puedes cambiarlas de sitio si reorganizas el texto, pero no las borres, no las modifiques y no las repitas.\n";

        return match ($mode) {
            AiModeEnum::Create => $base.
                "MODO: CREACIÓN DE NUEVA PÁGINA\n".
                "Debes proponer un título descriptivo y claro para la página, un slug limpio (minúsculas, separado por guiones y sin tildes) y redactar el contenido completo.\n".
                "ESTRUCTURA OBLIGATORIA AL INICIO:\n".
                "---\n".
                "title: Título de la página\n".
                "slug: slug-de-la-pagina\n".
                "---\n\n".
                "A continuación de ese bloque delimitado por '---', escribe el contenido íntegro en formato Markdown estructurado.\n".
                'Usa encabezados a partir de h3 (###), párrafos claros, listas o tablas según sea necesario.',

            AiModeEnum::Edit => $base.
                "MODO: EDICIÓN / AMPLIACIÓN DE PÁGINA ACTUAL\n".
                "Reescribe, amplía o adapta el texto según las instrucciones del usuario.\n".
                "Escribe directamente el contenido generado en formato Markdown estructurado (encabezados desde h3). No incluyas bloques '---' ni encabezados de título/slug.\n\n".
                $placeholders,

            AiModeEnum::Correct => $base.
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
        AiModeEnum $mode,
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

        if ($mode !== AiModeEnum::Create && filled($currentText)) {
            $language = $format === ContentPageFormatEnum::Html ? 'HTML' : 'Markdown';
            $context .= "\n\n=== CONTENIDO ACTUAL DE LA PÁGINA (en {$language}) ===\n{$currentText}\n";
        }

        return $context;
    }

    /**
     * La página actual tal como se le manda a la IA.
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
     * La instrucción editorial de la entrada: la de su perfil de editor, que
     * sustituye a la de la plataforma; sin perfil, la de la plataforma; y sin
     * ninguna, la genérica.
     */
    public function editorialInstruction(Content $content): string
    {
        $profile = $this->editorProfile($content);

        if ($profile !== null) {
            return $profile->system_instruction;
        }

        $platform = $content->platform;

        if ($platform instanceof Platform && filled(trim((string) $platform->system_instruction))) {
            return (string) $platform->system_instruction;
        }

        return self::DEFAULT_EDITORIAL_INSTRUCTION;
    }

    /**
     * De dónde sale la instrucción editorial, para enseñarlo en el modal.
     */
    public function editorialSource(Content $content): string
    {
        $profile = $this->editorProfile($content);

        if ($profile !== null) {
            return 'Perfil «'.$profile->name.'»';
        }

        $platform = $content->platform;

        return $platform instanceof Platform && filled(trim((string) $platform->system_instruction))
            ? 'El de la plataforma ('.$platform->title.')'
            : 'El genérico (la plataforma no tiene uno propio)';
    }

    /**
     * El perfil de la entrada si es de su plataforma y tiene instrucción.
     */
    private function editorProfile(Content $content): ?PlatformEditorProfile
    {
        $profile = $content->editorProfile;

        return $profile instanceof PlatformEditorProfile
            && (int) $profile->platform_id === (int) $content->platform_id
            && filled(trim($profile->system_instruction))
            ? $profile
            : null;
    }

    /**
     * Pide el texto al proveedor en streaming y lo va pasando a `$callback`
     * (ver `AiTextProvider::stream()`).
     *
     * @param  Closure(string $type, string $text): void  $callback
     */
    public function streamGenerateContent(
        AiProvider $provider,
        string $systemInstruction,
        string $userPrompt,
        ?AiReasoningLevelEnum $level,
        Closure $callback,
    ): void {
        $provider->connector()->stream($provider, $systemInstruction, $userPrompt, $provider->sends_reasoning_level ? $level : null, $callback);
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
     * La IA responde en Markdown, salvo al corregir una página HTML, que
     * responde en HTML. Antes de convertir se ponen en su sitio los bloques
     * de las marcas, y se avisa de los que haya quitado.
     *
     * @param  array<int, string>  $placeholders  Los de `prepareCurrentContent()`.
     * @return array{content: string, warnings: list<string>}
     */
    public function convertResultToFormat(
        string $generatedText,
        ContentPageFormatEnum $targetFormat,
        AiModeEnum $mode,
        array $placeholders = [],
    ): array {
        $answersInHtml = $mode === AiModeEnum::Correct && $targetFormat === ContentPageFormatEnum::Html;
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
     * lugar del párrafo en el que la IA lo haya dejado.
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
            'La IA ha quitado %s que no podía editar (%s). No están en el resultado: si los quieres, no lo apliques o vuelve a añadirlos.',
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
