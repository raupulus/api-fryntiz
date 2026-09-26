<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Helpers\TextFormatParseHelper;
use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser;
use League\HTMLToMarkdown\HtmlConverter;
use stdClass;
use Throwable;

/**
 * Convierte el contenido de una página entre Editor.js, Markdown y HTML.
 *
 * Los tres formatos no son equivalentes: Editor.js tiene bloques que Markdown no
 * sabe expresar (alertas, tarjetas de enlace, vídeos, imágenes enlazadas al
 * módulo de ficheros…). Para no perder nada, un bloque sólo se escribe en
 * Markdown «de verdad» si al volver a leerlo sale el mismo bloque con el mismo
 * texto, enlaces, imágenes y clases (`survivesRoundTrip()`). El resto va como
 * HTML dentro de un envoltorio:
 *
 *     <div data-editorjs-block="…base64 del bloque original…">
 *     …el HTML que genera ese bloque…
 *     </div>
 *
 * La web ve el HTML del bloque (el envoltorio se quita al servir, ver
 * `toServedHtml()`), y al volver a Editor.js el bloque se recupera tal cual,
 * salvo que se haya cambiado el HTML de dentro: el envoltorio guarda una huella
 * de ese HTML y, si no coincide, lo que vuelve es un bloque HTML (`raw`) con los
 * cambios, nunca el bloque viejo pisando lo que se editó.
 *
 * El HTML que se sirve de una página Editor.js lo sigue generando
 * `TextFormatParseHelper`, el mismo que en la v1: regenerarlo da exactamente el
 * HTML que ya había en producción.
 */
class ContentFormatConverter
{
    /**
     * Atributo del envoltorio que guarda el bloque original de Editor.js.
     */
    public const BLOCK_ATTRIBUTE = 'data-editorjs-block';

    /**
     * Versión de Editor.js que se declara en el JSON generado.
     */
    private const EDITORJS_VERSION = '2.31.7';

    /**
     * Niveles de título que admite el editor. El h1 y el h2 son de la web (el
     * título del contenido y el de la página), así que los títulos del texto
     * empiezan en h3.
     */
    public const MIN_HEADER_LEVEL = 3;

    /**
     * Nombre de cada tipo de bloque en los avisos.
     */
    private const BLOCK_LABELS = [
        'paragraph' => 'párrafo',
        'header' => 'título',
        'list' => 'lista',
        'checklist' => 'lista de tareas',
        'quote' => 'cita',
        'code' => 'código',
        'delimiter' => 'separador',
        'table' => 'tabla',
        'image' => 'imagen',
        'linkTool' => 'tarjeta de enlace',
        'embed' => 'vídeo incrustado',
        'alert' => 'alerta',
        'warning' => 'aviso',
        'attaches' => 'adjunto',
        'raw' => 'HTML',
    ];

    /**
     * Etiquetas en línea: seguidas, forman un párrafo.
     */
    private const INLINE_TAGS = [
        'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'i', 'ins',
        'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var',
    ];

    /**
     * Etiquetas de bloque que no pueden ir dentro de un texto en línea de Markdown.
     */
    private const BLOCK_TAG_PATTERN = '/<\/?(address|article|aside|audio|blockquote|details|div|dl|dd|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hr|iframe|li|main|nav|ol|p|pre|section|summary|table|tbody|td|tfoot|th|thead|tr|ul|video)\b/i';

    private ?HtmlConverter $htmlToMarkdown = null;

    private ?MarkdownConverter $markdown = null;

    /**
     * Convierte `$content` de un formato a otro para seguir editándolo.
     *
     * No guarda nada: es lo que el panel enseña antes de aceptar el cambio.
     */
    public function convert(string $content, ContentPageFormatEnum $from, ContentPageFormatEnum $to): ContentConversion
    {
        return match ($from->value.'>'.$to->value) {
            'editorjs>editorjs', 'markdown>markdown', 'html>html' => new ContentConversion($content),
            'editorjs>html' => $this->editorJsToHtml($content),
            'editorjs>markdown' => $this->editorJsToMarkdown($content),
            'markdown>html' => new ContentConversion($this->renderMarkdown($content)),
            'markdown>editorjs' => $this->htmlToEditorJs($this->renderMarkdown($content)),
            'html>editorjs' => $this->htmlToEditorJs($content),
            'html>markdown' => $this->htmlToMarkdown($content),
        };
    }

    /**
     * HTML que se sirve a la web (`content_pages.content`) a partir de la fuente.
     */
    public function toServedHtml(string $content, ContentPageFormatEnum $format): string
    {
        return match ($format) {
            ContentPageFormatEnum::EditorJs => TextFormatParseHelper::arrayToHtml($this->decodeBlocks($content)),
            ContentPageFormatEnum::Markdown => $this->unwrapBlocks($this->renderMarkdown($content)),
            ContentPageFormatEnum::Html => $this->unwrapBlocks($content),
        };
    }

    /**
     * Cuántos títulos h1 y h2 tiene un HTML.
     *
     * Markdown y HTML los guardan tal cual, pero en la web esos niveles son el
     * título del contenido y el de la página: al guardar se avisa, sin impedirlo.
     */
    public function topLevelHeadings(string $html): int
    {
        return $this->parseFragment($html)->querySelectorAll('h1, h2')->length;
    }

    /**
     * Bloques de un JSON de Editor.js.
     *
     * @return list<array<string, mixed>>
     *
     * @throws InvalidArgumentException si no es un JSON de Editor.js.
     */
    public function decodeBlocks(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            throw new InvalidArgumentException('El contenido no es un JSON de Editor.js: falta la clave «blocks».');
        }

        $blocks = array_filter($decoded['blocks'], fn ($block): bool => is_array($block) && isset($block['type']));

        return array_values(array_map(
            fn (array $block): array => $block + ['id' => Str::random(10), 'data' => []],
            $blocks,
        ));
    }

    /**
     * JSON de Editor.js con estos bloques.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public function encodeBlocks(array $blocks, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        return json_encode([
            'time' => (int) floor(microtime(true) * 1000),
            'blocks' => array_values(array_map($this->listMetaAsObject(...), $blocks)),
            'version' => self::EDITORJS_VERSION,
        ], $pretty ? $flags | JSON_PRETTY_PRINT : $flags);
    }

    /**
     * `meta` vacío como `{}` y no como `[]`, igual que lo guarda el editor.
     *
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function listMetaAsObject(array $block): array
    {
        if (($block['type'] ?? null) !== 'list' || ! is_array($block['data'] ?? null) || ! array_key_exists('meta', $block['data'])) {
            return $block;
        }

        $fix = function (array $node) use (&$fix): array {
            if (($node['meta'] ?? null) === []) {
                $node['meta'] = new stdClass;
            }

            if (is_array($node['items'] ?? null)) {
                $node['items'] = array_map(fn ($item) => is_array($item) ? $fix($item) : $item, $node['items']);
            }

            return $node;
        };

        $block['data'] = $fix($block['data']);

        return $block;
    }

    // ── Editor.js → HTML / Markdown ─────────────────────────────────────────

    private function editorJsToHtml(string $json): ContentConversion
    {
        $blocks = $this->decodeBlocks($json);

        $html = implode("\n\n", array_map(fn (array $block): string => $this->wrapBlock($block), $blocks));

        return new ContentConversion($html, $blocks === [] ? [] : [
            'Cada bloque va dentro de un <div data-editorjs-block> que guarda el bloque original. La web ve el mismo HTML que ahora; si vuelves a Editor.js, los bloques que no hayas tocado se recuperan tal cual y los que cambies vuelven como bloque HTML.',
        ]);
    }

    private function editorJsToMarkdown(string $json): ContentConversion
    {
        [$markdown, $warnings] = $this->blocksToMarkdown($this->decodeBlocks($json));

        return new ContentConversion($markdown, $warnings);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array{0: string, 1: list<string>}
     */
    private function blocksToMarkdown(array $blocks): array
    {
        $parts = [];
        $wrapped = [];
        $native = [];

        foreach ($blocks as $block) {
            $markdown = $this->blockToMarkdown($block);

            if ($markdown !== null && ! $this->survivesRoundTrip($block, $markdown)) {
                $markdown = null;
            }

            if ($markdown === null) {
                $parts[] = $this->wrapBlock($block);
                $wrapped[] = (string) $block['type'];

                continue;
            }

            // Dos listas seguidas son UNA en Markdown aunque las separe una
            // línea en blanco. Un comentario HTML las mantiene separadas y en
            // la web no se ve.
            if (in_array($block['type'], ['list', 'checklist'], true) && in_array(end($native), ['list', 'checklist'], true)
                && ! str_starts_with((string) end($parts), '<div '.self::BLOCK_ATTRIBUTE)) {
                $parts[] = '<!-- -->';
            }

            $parts[] = $markdown;
            $native[] = (string) $block['type'];
        }

        $warnings = [];

        if ($native !== []) {
            $warnings[] = sprintf(
                '%s %s a Markdown (%s). Quien pida la página en HTML %s recibirá como HTML estándar, sin las clases r-* que genera Editor.js, así que puede cambiar su aspecto en esa web.',
                $this->plural(count($native), 'bloque', 'bloques'),
                count($native) === 1 ? 'pasa' : 'pasan',
                $this->detail($native),
                count($native) === 1 ? 'lo' : 'los',
            );
        }

        if ($wrapped !== []) {
            $total = count($wrapped);

            $warnings[] = sprintf(
                '%s sin equivalente exacto en Markdown (%s) %s como HTML dentro del Markdown: se ve igual en la web y, si vuelves a Editor.js sin tocarlo, se recupera tal cual. Si cambias ese HTML, vuelve como bloque HTML.',
                $this->plural($total, 'bloque', 'bloques'),
                $this->detail($wrapped),
                $total === 1 ? 'va' : 'van',
            );
        }

        return [$parts === [] ? '' : implode("\n\n", $parts)."\n", $warnings];
    }

    /**
     * Markdown de un bloque, o null si Markdown no lo expresa.
     *
     * @param  array<string, mixed>  $block
     */
    private function blockToMarkdown(array $block): ?string
    {
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        // Los tunes (cita, destacado, detalles…) no existen en Markdown.
        if (collect($block['tunes'] ?? [])->filter(fn ($tune): bool => filled($tune))->isNotEmpty()) {
            return null;
        }

        return match ($block['type']) {
            'paragraph' => $this->paragraphToMarkdown($data),
            'header' => $this->headerToMarkdown($data),
            'list' => $this->listToMarkdown($data),
            'checklist' => $this->checklistToMarkdown($data),
            'quote' => $this->quoteToMarkdown($data),
            'code' => $this->codeToMarkdown($data),
            'delimiter' => '---',
            'table' => $this->tableToMarkdown($data),
            'image' => $this->imageToMarkdown($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function paragraphToMarkdown(array $data): ?string
    {
        $text = $data['text'] ?? null;

        // Un párrafo vacío es un hueco en la página; en Markdown desaparecería.
        if (! is_string($text) || trim(strip_tags($text, '<img>')) === '' || preg_match(self::BLOCK_TAG_PATTERN, $text)) {
            return null;
        }

        return $this->inlineToMarkdown($text);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function headerToMarkdown(array $data): ?string
    {
        $text = $data['text'] ?? null;
        $level = (int) ($data['level'] ?? 0);

        if (! is_string($text) || $level < 1 || $level > 6 || trim($text) === '' || $text !== strip_tags($text)) {
            return null;
        }

        return str_repeat('#', $level).' '.$this->inlineToMarkdown($text);
    }

    /**
     * Listas de `@editorjs/list` en cualquiera de sus formatos (ver
     * `TextFormatParseHelper::listItems()`), también anidadas y de casillas.
     *
     * @param  array<string, mixed>  $data
     */
    private function listToMarkdown(array $data): ?string
    {
        $items = TextFormatParseHelper::listItems($data['items'] ?? null);
        $style = $data['style'] ?? 'unordered';
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        // Markdown sólo numera con cifras.
        if ($items === [] || ! in_array($meta['counterType'] ?? 'numeric', ['numeric', null], true)) {
            return null;
        }

        return $this->listItemsToMarkdown(
            $items,
            is_string($style) ? $style : 'unordered',
            max(1, (int) ($meta['start'] ?? 1)),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function checklistToMarkdown(array $data): ?string
    {
        $items = TextFormatParseHelper::listItems($data['items'] ?? null);

        return $items === [] ? null : $this->listItemsToMarkdown($items, 'checklist', 1);
    }

    /**
     * @param  list<array{content: string, checked: bool, items: list<array<string, mixed>>}>  $items
     */
    private function listItemsToMarkdown(array $items, string $style, int $start): ?string
    {
        $lines = [];

        foreach ($items as $index => $item) {
            $content = $item['content'];

            if (preg_match(self::BLOCK_TAG_PATTERN, $content)
                || ($style === 'checklist' && trim(strip_tags($content)) === '')) {
                return null;
            }

            $marker = match ($style) {
                'ordered' => ($start + $index).'. ',
                'checklist' => '- ['.($item['checked'] ? 'x' : ' ').'] ',
                default => '- ',
            };

            // Lo que va dentro del elemento se sangra hasta donde empieza su
            // texto; en las de casillas, el texto empieza en `[ ]`.
            $width = $style === 'checklist' ? 2 : strlen($marker);
            $line = $marker.$this->indent($this->inlineToMarkdown($content), $style === 'checklist' ? 6 : $width);

            if ($item['items'] !== []) {
                /** @var list<array{content: string, checked: bool, items: list<array<string, mixed>>}> $children */
                $children = $item['items'];
                $nested = $this->listItemsToMarkdown($children, $style, 1);

                if ($nested === null) {
                    return null;
                }

                $line .= "\n".str_repeat(' ', $width).$this->indent($nested, $width);
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function quoteToMarkdown(array $data): ?string
    {
        $text = $data['text'] ?? null;
        $caption = $data['caption'] ?? '';

        if (! is_string($text) || ! is_string($caption) || ($data['alignment'] ?? 'left') !== 'left'
            || preg_match(self::BLOCK_TAG_PATTERN, $text.$caption)) {
            return null;
        }

        $lines = explode("\n", $this->inlineToMarkdown($text));

        if (trim(strip_tags($caption)) !== '') {
            $lines[] = '';
            $lines[] = '— '.$this->inlineToMarkdown($caption);
        }

        return implode("\n", array_map(fn (string $line): string => $line === '' ? '>' : '> '.$line, $lines));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function codeToMarkdown(array $data): ?string
    {
        $code = $data['code'] ?? null;

        if (! is_string($code)) {
            return null;
        }

        $language = preg_replace('/[^A-Za-z0-9_+#.-]/', '', (string) ($data['language'] ?? ''));

        // La valla tiene que ser más larga que cualquier racha de ` del código.
        preg_match_all('/`{3,}/', $code, $runs);
        $longest = $runs[0] === [] ? 0 : max(array_map('strlen', $runs[0]));
        $fence = str_repeat('`', max(3, $longest + 1));

        return $fence.$language."\n".$code."\n".$fence;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function tableToMarkdown(array $data): ?string
    {
        $rows = $data['content'] ?? null;

        // Markdown no tiene tablas sin cabecera.
        if (empty($data['withHeadings']) || ! empty($data['stretched']) || ! is_array($rows) || $rows === []) {
            return null;
        }

        $width = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return null;
            }

            foreach ($row as $cell) {
                if (! is_string($cell) || $cell !== strip_tags($cell) || str_contains($cell, "\n")) {
                    return null;
                }
            }

            $width = max($width, count($row));
        }

        if ($width === 0) {
            return null;
        }

        $line = function (array $cells) use ($width): string {
            $cells = array_pad(array_values($cells), $width, '');

            return '| '.implode(' | ', array_map(fn (string $cell): string => $this->escapeText($cell), $cells)).' |';
        };

        $rows = array_values($rows);
        $lines = [$line($rows[0]), '|'.str_repeat(' --- |', $width)];

        foreach (array_slice($rows, 1) as $row) {
            $lines[] = $line($row);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function imageToMarkdown(array $data): ?string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $url = $file['url'] ?? null;

        // Una imagen del módulo de ficheros, con pie o con estilos no cabe en
        // `![]()`: se perdería el enlace con el fichero o lo que se ve.
        if (! is_string($url) || $url === '' || isset($file['file_id']) || filled(strip_tags((string) ($data['caption'] ?? '')))
            || ! empty($data['withBorder']) || ! empty($data['stretched']) || ! empty($data['withBackground'])) {
            return null;
        }

        $alt = str_replace(['[', ']'], ['\[', '\]'], (string) ($file['alt'] ?? ''));

        return '!['.$alt.']('.(preg_match('/[\s()<>]/', $url) ? '<'.$url.'>' : $url).')';
    }

    /**
     * ¿Leer este Markdown devuelve el mismo bloque, con el mismo aspecto?
     *
     * Es la red de seguridad contra errores del propio conversor: escapes,
     * saltos de línea, entidades… Si algo no cuadra, el bloque va envuelto y no
     * se pierde nada.
     *
     * @param  array<string, mixed>  $block
     */
    private function survivesRoundTrip(array $block, string $markdown): bool
    {
        [$back] = $this->parseHtmlToBlocks($this->renderMarkdown($markdown), forEditorJs: false);

        if (count($back) !== 1 || $this->kind($back[0]['type']) !== $this->kind($block['type'])) {
            return false;
        }

        return $this->visibleSignature($this->renderBlock($block)) === $this->visibleSignature($this->renderBlock($back[0]));
    }

    /**
     * Una lista de casillas puede ser un bloque `checklist` o un `list` con
     * `style: checklist`: se pintan igual, así que da lo mismo cuál vuelva.
     */
    private function kind(mixed $type): string
    {
        return $type === 'checklist' ? 'list' : (string) $type;
    }

    // ── HTML / Markdown → Editor.js ─────────────────────────────────────────

    private function htmlToEditorJs(string $html): ContentConversion
    {
        [$blocks, $stats] = $this->parseHtmlToBlocks($html);

        return new ContentConversion(
            $this->encodeBlocks($blocks, pretty: true),
            $this->htmlWarnings($stats, forMarkdown: false),
            $this->summary($blocks),
        );
    }

    private function htmlToMarkdown(string $html): ContentConversion
    {
        [$blocks, $stats] = $this->parseHtmlToBlocks($html, forEditorJs: false);
        [$markdown, $warnings] = $this->blocksToMarkdown($blocks);

        return new ContentConversion($markdown, [...$this->htmlWarnings($stats, forMarkdown: true), ...$warnings]);
    }

    /**
     * Recorre el HTML de primer nivel y lo convierte en bloques de Editor.js.
     *
     * @return array{0: list<array<string, mixed>>, 1: array<string, int>}
     */
    private function parseHtmlToBlocks(string $html, bool $forEditorJs = true): array
    {
        $body = $this->parseFragment($html);
        $stats = ['raw' => 0, 'edited' => 0, 'restored' => 0, 'mixed' => 0, 'formatting' => 0, 'demoted' => 0];
        $blocks = [];
        $inline = '';

        $flush = function () use (&$inline, &$blocks): void {
            $text = $this->cleanInline($inline);

            if (trim(strip_tags($text, '<img>')) !== '') {
                $blocks[] = $this->block('paragraph', ['text' => $text]);
            }

            $inline = '';
        };

        foreach (iterator_to_array($body->childNodes) as $node) {
            if ($node instanceof Text) {
                $inline .= $this->outerHtml($node);

                continue;
            }

            if (! $node instanceof Element) {
                continue;
            }

            $tag = strtolower($node->localName);

            if (in_array($tag, self::INLINE_TAGS, true)) {
                $inline .= $this->outerHtml($node);

                continue;
            }

            $flush();

            $block = $this->elementToBlock($node, $tag, $stats, $forEditorJs);

            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        $flush();

        return [$blocks, $stats];
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>|null
     */
    private function elementToBlock(Element $element, string $tag, array &$stats, bool $forEditorJs): ?array
    {
        if (preg_match('/^h([1-6])$/', $tag, $level) === 1) {
            if ($element->firstElementChild !== null) {
                $stats['formatting']++;
            }

            $text = $this->collapse($element->textContent);

            if ($text === '') {
                return null;
            }

            $level = (int) $level[1];

            // Markdown y HTML sí guardan el h1 y el h2 (con un aviso al guardar).
            if ($forEditorJs && $level < self::MIN_HEADER_LEVEL) {
                $stats['demoted']++;
                $level = self::MIN_HEADER_LEVEL;
            }

            return $this->block('header', ['text' => $text, 'level' => $level]);
        }

        return match (true) {
            $tag === 'div' && $element->hasAttribute(self::BLOCK_ATTRIBUTE) => $this->restoreBlock($element, $stats),
            $tag === 'p' => $this->paragraphBlock($element),
            $tag === 'ul', $tag === 'ol' => $this->listBlock($element, $tag, $stats),
            $tag === 'blockquote' => $this->quoteBlock($element, $stats),
            $tag === 'pre' => $this->codeBlock($element),
            $tag === 'hr' => $this->block('delimiter', []),
            $tag === 'table' => $this->tableBlock($element, $stats),
            $tag === 'img' => $this->imageBlock($element, ''),
            $tag === 'figure' => $this->figureBlock($element, $stats),
            default => $this->rawBlock($element, $stats),
        };
    }

    /**
     * El bloque que se guardó en un envoltorio, si nadie ha tocado su HTML.
     *
     * @param  array<string, int>  $stats
     * @return array<string, mixed>|null
     */
    private function restoreBlock(Element $wrapper, array &$stats): ?array
    {
        $payload = json_decode((string) base64_decode($wrapper->getAttribute(self::BLOCK_ATTRIBUTE) ?? '', true), true);
        $block = is_array($payload) ? ($payload['block'] ?? null) : null;

        if (is_array($block) && isset($block['type']) && ($payload['hash'] ?? null) === $this->fingerprint($wrapper->innerHTML)) {
            $stats['restored']++;

            return $block;
        }

        $stats['edited']++;
        $inner = trim($wrapper->innerHTML);

        return $inner === '' ? null : $this->block('raw', ['html' => $this->cleanRaw($inner)]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paragraphBlock(Element $paragraph): ?array
    {
        $children = $this->elementChildren($paragraph);
        $hasText = trim(str_replace("\u{00A0}", ' ', $this->directText($paragraph))) !== '';

        // `![alt](url)` sale como un párrafo con sólo la imagen.
        if (! $hasText && count($children) === 1 && strtolower($children[0]->localName) === 'img') {
            return $this->imageBlock($children[0], '');
        }

        $text = $this->cleanInline($paragraph->innerHTML);

        return trim(strip_tags($text, '<img>')) === '' ? null : $this->block('paragraph', ['text' => $text]);
    }

    /**
     * Una lista de HTML como bloque de `@editorjs/list` 2.x, anidadas incluidas.
     *
     * Las de casillas sin anidar siguen siendo un bloque `checklist`, el de la
     * herramienta propia. Lo que la herramienta no sabe guardar va como bloque
     * HTML: bloques dentro de un elemento (código, citas, tablas…) o niveles
     * que mezclan numerada y con viñetas.
     *
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function listBlock(Element $list, string $tag, array &$stats): array
    {
        // Se lee una copia: al leer se quitan casillas y sublistas, y si al
        // final va como bloque HTML tiene que ir entera.
        $copy = $list->cloneNode(true);
        $first = $list->querySelector('li');
        $checklist = $first !== null && $this->checkbox($first) !== null;
        $mixed = false;
        $items = $copy instanceof Element ? $this->listItemsFromHtml($copy, $tag, $checklist, $mixed) : null;

        if ($items === null) {
            $stats[$mixed ? 'mixed' : 'raw']++;

            return $this->rawBlock($list, $stats, count: false);
        }

        $nested = array_filter($items, fn (array $item): bool => $item['items'] !== []) !== [];

        if ($checklist && ! $nested) {
            return $this->block('checklist', ['items' => array_map(
                fn (array $item): array => ['text' => $item['content'], 'checked' => $item['meta']['checked']],
                $items,
            )]);
        }

        $meta = [];

        if ($tag === 'ol') {
            $meta['counterType'] = match ((string) $list->getAttribute('type')) {
                'a' => 'lower-alpha',
                'A' => 'upper-alpha',
                'i' => 'lower-roman',
                'I' => 'upper-roman',
                default => 'numeric',
            };

            $start = (int) $list->getAttribute('start');

            if ($start > 1) {
                $meta['start'] = $start;
            }
        }

        return $this->block('list', [
            'style' => $checklist ? 'checklist' : ($tag === 'ol' ? 'ordered' : 'unordered'),
            'meta' => $meta,
            'items' => $items,
        ]);
    }

    /**
     * Elementos de una lista de HTML en el formato de `@editorjs/list` 2.x, o
     * null si la herramienta no puede guardarla tal cual.
     *
     * @return list<array{content: string, meta: array<string, bool>, items: list<array<string, mixed>>}>|null
     */
    private function listItemsFromHtml(Element $list, string $tag, bool $checklist, bool &$mixed): ?array
    {
        $children = $this->elementChildren($list);
        $items = [];

        if ($children === []) {
            return null;
        }

        foreach ($children as $item) {
            if (strtolower($item->localName) !== 'li'
                || $item->querySelector('pre, blockquote, table, div, figure, h1, h2, h3, h4, h5, h6') !== null) {
                return null;
            }

            $checkbox = $this->checkbox($item);

            if (($checkbox !== null) !== $checklist) {
                return null;
            }

            // Una sublista como mucho, y al final del elemento.
            $sublists = array_values(array_filter(
                $this->elementChildren($item),
                fn (Element $child): bool => in_array(strtolower($child->localName), ['ul', 'ol'], true),
            ));

            $nestedItems = [];

            if ($sublists !== []) {
                $sublist = $sublists[0];

                if (count($sublists) > 1 || $sublist->nextElementSibling !== null) {
                    return null;
                }

                if (strtolower($sublist->localName) !== $tag) {
                    $mixed = true;

                    return null;
                }

                $nestedItems = $this->listItemsFromHtml($sublist, $tag, $checklist, $mixed);

                if ($nestedItems === null) {
                    return null;
                }

                $sublist->remove();
            }

            $checkbox?->remove();

            $items[] = [
                'content' => $this->listItemHtml($item),
                'meta' => $checklist ? ['checked' => $checkbox?->hasAttribute('checked') ?? false] : [],
                'items' => $nestedItems,
            ];
        }

        return $items;
    }

    /**
     * La casilla con la que empieza un elemento de una lista de tareas.
     */
    private function checkbox(Element $item): ?Element
    {
        $first = $item->firstElementChild;

        // En una lista «holgada» la casilla va dentro del primer <p>.
        if ($first !== null && strtolower($first->localName) === 'p') {
            $first = $first->firstElementChild;
        }

        return $first !== null && strtolower($first->localName) === 'input'
            && strtolower((string) $first->getAttribute('type')) === 'checkbox' ? $first : null;
    }

    private function listItemHtml(Element $item): string
    {
        $paragraphs = array_filter(
            $this->elementChildren($item),
            fn (Element $child): bool => strtolower($child->localName) === 'p',
        );

        // Lista «holgada»: cada elemento viene en uno o varios <p>.
        if ($paragraphs !== [] && count($paragraphs) === count($this->elementChildren($item))) {
            return implode('<br>', array_map(fn (Element $p): string => $this->cleanInline($p->innerHTML), $paragraphs));
        }

        return $this->cleanInline($item->innerHTML);
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function quoteBlock(Element $quote, array &$stats): array
    {
        $children = $this->elementChildren($quote);
        $paragraphs = array_values(array_filter($children, fn (Element $child): bool => strtolower($child->localName) === 'p'));

        if ($paragraphs === [] || count($paragraphs) !== count($children) || trim($this->directText($quote)) !== '') {
            return $this->rawBlock($quote, $stats);
        }

        $caption = '';
        $last = end($paragraphs);

        if (count($paragraphs) > 1 && str_starts_with(trim($last->textContent), '—')) {
            $caption = (string) preg_replace('/^\s*(—|&mdash;)\s*/u', '', $this->cleanInline($last->innerHTML));
            array_pop($paragraphs);
        }

        return $this->block('quote', [
            'text' => implode('<br>', array_map(fn (Element $p): string => $this->cleanInline($p->innerHTML), $paragraphs)),
            'caption' => $caption,
            'alignment' => 'left',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function codeBlock(Element $pre): array
    {
        $code = $pre->textContent;

        // CommonMark termina siempre el bloque con un salto de línea.
        if (str_ends_with($code, "\n")) {
            $code = substr($code, 0, -1);
        }

        $data = ['code' => $code];
        $class = (string) ($pre->querySelector('code')?->getAttribute('class') ?? '');

        if (preg_match('/language-([\w+#.-]+)/', $class, $language) === 1) {
            $data['language'] = $language[1];
        }

        return $this->block('code', $data + ['showlinenumbers' => true]);
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function tableBlock(Element $table, array &$stats): array
    {
        if ($table->querySelector('table') !== null || $table->querySelector('[colspan], [rowspan]') !== null) {
            return $this->rawBlock($table, $stats);
        }

        $rows = [];
        $width = 0;

        foreach (iterator_to_array($table->querySelectorAll('tr')) as $row) {
            $cells = [];

            foreach ($this->elementChildren($row) as $cell) {
                if (! in_array(strtolower($cell->localName), ['td', 'th'], true)) {
                    continue;
                }

                if ($cell->firstElementChild !== null) {
                    $stats['formatting']++;
                }

                $cells[] = $this->collapse($cell->textContent);
            }

            $rows[] = $cells;
            $width = max($width, count($cells));
        }

        if ($rows === [] || $width === 0) {
            return $this->rawBlock($table, $stats);
        }

        $firstRow = $table->querySelector('tr');
        $withHeadings = $table->querySelector('thead tr') !== null
            || ($firstRow !== null && $firstRow->querySelector('td') === null);

        return $this->block('table', [
            'withHeadings' => $withHeadings,
            'stretched' => false,
            'content' => array_map(fn (array $cells): array => array_pad($cells, $width, ''), $rows),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function imageBlock(Element $image, string $caption): ?array
    {
        $src = trim((string) $image->getAttribute('src'));

        if ($src === '') {
            return null;
        }

        return $this->block('image', [
            'file' => ['url' => $src, 'alt' => (string) ($image->getAttribute('alt') ?? '')],
            'caption' => $caption,
            'withBorder' => false,
            'stretched' => false,
            'withBackground' => false,
        ]);
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>|null
     */
    private function figureBlock(Element $figure, array &$stats): ?array
    {
        $children = $this->elementChildren($figure);
        $names = array_map(fn (Element $child): string => strtolower($child->localName), $children);
        $image = $figure->querySelector('img');

        if ($image === null || ! in_array('img', $names, true) || array_diff($names, ['img', 'figcaption']) !== []) {
            return $this->rawBlock($figure, $stats);
        }

        return $this->imageBlock($image, $this->collapse((string) $figure->querySelector('figcaption')?->textContent));
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function rawBlock(Element $element, array &$stats, bool $count = true): array
    {
        if ($count) {
            $stats['raw']++;
        }

        return $this->block('raw', ['html' => $this->cleanRaw($this->outerHtml($element))]);
    }

    /**
     * @param  array<string, int>  $stats
     * @return list<string>
     */
    private function htmlWarnings(array $stats, bool $forMarkdown): array
    {
        $warnings = [];

        if ($stats['restored'] > 0 && ! $forMarkdown) {
            $warnings[] = $stats['restored'] === 1
                ? '1 bloque de Editor.js guardado en el texto se recupera tal cual estaba.'
                : "{$stats['restored']} bloques de Editor.js guardados en el texto se recuperan tal cual estaban.";
        }

        if ($stats['edited'] > 0) {
            $warnings[] = $stats['edited'] === 1
                ? '1 bloque de Editor.js guardado en el texto se había cambiado: vuelve como bloque HTML con esos cambios, no como el bloque original.'
                : "{$stats['edited']} bloques de Editor.js guardados en el texto se habían cambiado: vuelven como bloque HTML con esos cambios, no como el bloque original.";
        }

        if ($stats['raw'] > 0 && ! $forMarkdown) {
            $warnings[] = sprintf(
                '%s de HTML sin bloque equivalente en Editor.js %s como bloque HTML: se ve igual, pero se edita como código.',
                $this->plural($stats['raw'], 'trozo', 'trozos'),
                $stats['raw'] === 1 ? 'va' : 'van',
            );
        }

        if ($stats['mixed'] > 0) {
            $warnings[] = sprintf(
                '%s numerada y con viñetas en sus niveles: %s como bloque HTML, porque en Editor.js todos los niveles de una lista son del mismo tipo.',
                $stats['mixed'] === 1 ? '1 lista mezcla' : $stats['mixed'].' listas mezclan',
                $stats['mixed'] === 1 ? 'va' : 'van',
            );
        }

        if ($stats['demoted'] > 0) {
            $warnings[] = sprintf(
                '%s de nivel 1 o 2 %s a nivel 3: el h1 y el h2 los pone la web (título del contenido y de la página), y el editor sólo tiene títulos del 3 al 6.',
                $this->plural($stats['demoted'], 'título', 'títulos'),
                $stats['demoted'] === 1 ? 'pasa' : 'pasan',
            );
        }

        if ($stats['formatting'] > 0) {
            $warnings[] = sprintf(
                'Se pierde el formato (negrita, enlaces…) de %s de tabla: esos bloques sólo guardan texto.',
                $this->plural($stats['formatting'], 'título o celda', 'títulos o celdas'),
            );
        }

        return $warnings;
    }

    // ── Envoltorio de bloques ───────────────────────────────────────────────

    /**
     * HTML de un bloque dentro del envoltorio que permite recuperarlo.
     *
     * @param  array<string, mixed>  $block
     */
    public function wrapBlock(array $block): string
    {
        $inner = $this->renderBlock($block);

        $payload = base64_encode(json_encode(
            ['block' => $block, 'hash' => $this->fingerprint($inner)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        // Sin líneas en blanco dentro: en Markdown cortarían el bloque HTML.
        return $inner === ''
            ? '<div '.self::BLOCK_ATTRIBUTE.'="'.$payload.'"></div>'
            : '<div '.self::BLOCK_ATTRIBUTE.'="'.$payload.'">'."\n".$inner."\n".'</div>';
    }

    /**
     * Quita los envoltorios y deja el HTML de cada bloque, que es lo que ve la web.
     */
    private function unwrapBlocks(string $html): string
    {
        if (! str_contains($html, self::BLOCK_ATTRIBUTE)) {
            return $html;
        }

        $body = $this->parseFragment($html);

        foreach (iterator_to_array($body->querySelectorAll('['.self::BLOCK_ATTRIBUTE.']')) as $wrapper) {
            $wrapper->replaceWith(...iterator_to_array($wrapper->childNodes));
        }

        return trim($body->innerHTML);
    }

    /**
     * HTML de un único bloque, el mismo que genera la página entera.
     *
     * @param  array<string, mixed>  $block
     */
    private function renderBlock(array $block): string
    {
        try {
            return TextFormatParseHelper::arrayToHtml([$block]);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Huella del HTML de un bloque, para saber si se ha tocado.
     */
    private function fingerprint(string $html): string
    {
        return sha1($this->collapse($this->parseFragment($html)->innerHTML));
    }

    /**
     * Lo que se ve de un trozo de HTML: texto, enlaces (y dónde se abren),
     * imágenes, clases y estilos.
     */
    private function visibleSignature(string $html): string
    {
        $body = $this->parseFragment($html);
        $parts = [$this->collapse($body->textContent)];

        foreach (iterator_to_array($body->querySelectorAll('*')) as $element) {
            foreach (['href', 'target', 'rel', 'src', 'class', 'style'] as $attribute) {
                if ($element->hasAttribute($attribute)) {
                    $parts[] = $element->localName.'@'.$attribute.'='.$this->collapse((string) $element->getAttribute($attribute));
                }
            }
        }

        return sha1(implode("\0", $parts));
    }

    // ── Utilidades ──────────────────────────────────────────────────────────

    /**
     * Markdown de GitHub sin `DisallowedRawHtmlExtension`.
     *
     * `Str::markdown()` usa el conversor de GitHub, que escapa los <iframe>: un
     * vídeo incrustado dentro del Markdown salía como texto. Quien escribe aquí
     * es un editor del panel, no un visitante, así que el HTML pasa tal cual;
     * lo que sí se corta son los enlaces `javascript:` en la sintaxis de Markdown.
     */
    private function renderMarkdown(string $markdown): string
    {
        return $this->markdownConverter()->convert($markdown)->getContent();
    }

    private function markdownConverter(): MarkdownConverter
    {
        if ($this->markdown === null) {
            $environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new AutolinkExtension);
            $environment->addExtension(new StrikethroughExtension);
            $environment->addExtension(new TableExtension);
            $environment->addExtension(new TaskListExtension);

            $this->markdown = new MarkdownConverter($environment);
        }

        return $this->markdown;
    }

    /**
     * Árbol de un Markdown, leído igual que cuando se sirve.
     */
    public function parseMarkdown(string $markdown): Document
    {
        return (new MarkdownParser($this->markdownConverter()->getEnvironment()))->parse($markdown);
    }

    /**
     * El bloque de Editor.js de un envoltorio `<div data-editorjs-block>`, si
     * el HTML es exactamente uno, y si su HTML de dentro sigue como se generó.
     *
     * Que siga intacto no quiere decir que sea de fiar: la huella no lleva
     * secreto y cualquiera puede calcularla. Sirve para saber si alguien lo ha
     * tocado sin querer, no para dar por bueno lo que trae.
     *
     * @return array{block: array<string, mixed>, intact: bool}|null
     */
    public function wrappedBlock(string $html): ?array
    {
        $body = $this->parseFragment($html);
        $children = $this->elementChildren($body);

        if (count($children) !== 1 || trim($this->directText($body)) !== '') {
            return null;
        }

        $wrapper = $children[0];

        if (strtolower($wrapper->localName) !== 'div' || ! $wrapper->hasAttribute(self::BLOCK_ATTRIBUTE)) {
            return null;
        }

        $payload = json_decode((string) base64_decode($wrapper->getAttribute(self::BLOCK_ATTRIBUTE) ?? '', true), true);
        $block = is_array($payload) ? ($payload['block'] ?? null) : null;

        if (! is_array($block) || ! is_string($block['type'] ?? null)) {
            return null;
        }

        return ['block' => $block, 'intact' => ($payload['hash'] ?? null) === $this->fingerprint($wrapper->innerHTML)];
    }

    /**
     * Texto en línea de Editor.js a Markdown.
     *
     * Antes se quitan los saltos de línea y se juntan los espacios igual que
     * hace `TextFormatParseHelper::arrayToHtml()`: el Markdown tiene que decir lo
     * que ya ve la web, no lo que se ve en el editor.
     */
    private function inlineToMarkdown(string $html): string
    {
        $html = (string) preg_replace('/\s{2,}/', ' ', (string) preg_replace('/\n|\r|\t/', '', $html));

        $this->htmlToMarkdown ??= new HtmlConverter([
            'header_style' => 'atx',
            'strip_tags' => false,
            'hard_break' => false,
            'use_autolinks' => false,
            'remove_nodes' => '',
            'preserve_comments' => false,
            'strip_placeholder_links' => false,
            'suppress_errors' => true,
            'italic_style' => '*',
            'bold_style' => '**',
        ]);

        return trim($this->htmlToMarkdown->convert($html), "\n");
    }

    /**
     * Texto plano con los caracteres de Markdown escapados.
     */
    private function escapeText(string $text): string
    {
        return (string) preg_replace('/([\\\\`*_\[\]<>|~])/', '\\\\$1', $text);
    }

    /**
     * Sangra las líneas de continuación para que sigan dentro del elemento de lista.
     */
    private function indent(string $markdown, int $width): string
    {
        return str_replace("\n", "\n".str_repeat(' ', $width), $markdown);
    }

    private function parseFragment(string $html): Element
    {
        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head></head><body>'.$html.'</body></html>',
            LIBXML_NOERROR,
            'UTF-8',
        );

        return $document->body;
    }

    private function outerHtml(Node $node): string
    {
        $document = $node->ownerDocument;

        return $document instanceof HTMLDocument ? $document->saveHtml($node) : '';
    }

    /**
     * @return list<Element>
     */
    private function elementChildren(Element $element): array
    {
        $children = [];

        for ($child = $element->firstElementChild; $child !== null; $child = $child->nextElementSibling) {
            $children[] = $child;
        }

        return $children;
    }

    /**
     * Texto que cuelga directamente del elemento, sin el de sus hijos.
     */
    private function directText(Element $element): string
    {
        $text = '';

        foreach (iterator_to_array($element->childNodes) as $node) {
            if ($node instanceof Text) {
                $text .= $node->textContent;
            }
        }

        return $text;
    }

    private function cleanInline(string $html): string
    {
        return trim((string) preg_replace('/\s*\n\s*/', ' ', $html));
    }

    /**
     * El bloque `raw` convierte cada salto de línea en un <br>: fuera de un
     * <pre> los saltos son sólo formato del código y se quitan.
     */
    private function cleanRaw(string $html): string
    {
        return str_contains($html, '<pre') ? trim($html) : $this->cleanInline($html);
    }

    private function collapse(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function block(string $type, array $data): array
    {
        return ['id' => Str::random(10), 'type' => $type, 'data' => $data];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function summary(array $blocks): string
    {
        if ($blocks === []) {
            return 'Sin bloques: la página queda vacía.';
        }

        return $this->plural(count($blocks), 'bloque', 'bloques').': '.$this->detail(array_column($blocks, 'type')).'.';
    }

    /**
     * «imagen ×2, alerta ×1».
     *
     * @param  list<string>  $types
     */
    private function detail(array $types): string
    {
        return collect($types)
            ->countBy()
            ->map(fn (int $count, string $type): string => (self::BLOCK_LABELS[$type] ?? $type).' ×'.$count)
            ->implode(', ');
    }

    private function plural(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }
}
