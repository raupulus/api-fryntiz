<?php

declare(strict_types=1);

namespace App\Services\Content;

use Illuminate\Validation\ValidationException;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;

/**
 * Limpia el HTML que un Editor escribe dentro de un Markdown (DUDA-4 del plan
 * de contenidos del 2026-09-24). Lo de los administradores no se toca.
 *
 * Markdown deja escribir HTML tal cual y la web lo recibe tal cual, así que un
 * `<img src=x onerror=…>` en un Markdown haría lo mismo que en un párrafo de
 * Editor.js. Se limpia con la misma lista de etiquetas (`ContentHtmlSanitizer`),
 * pero **sólo el HTML**: la sintaxis de Markdown, el código entre comillas y los
 * bloques de código no se tocan, y lo que ya estaba limpio queda igual.
 *
 * - HTML en línea (`<b>`, `<a href>`…): etiqueta a etiqueta. Las permitidas se
 *   quedan con sus atributos permitidos; las demás se quitan, dejando el texto.
 * - Bloques de HTML: se quedan en su texto con el formato en línea permitido.
 * - Envoltorios `<div data-editorjs-block>` (bloques de Editor.js que Markdown no
 *   sabe expresar): se limpia el bloque que guardan y se vuelve a generar su
 *   HTML con la plantilla. La huella no es de fiar (cualquiera la calcula), así
 *   que no se da por bueno lo que traen. Un bloque de HTML libre sólo pasa si ya
 *   estaba en la página; si no, no se guarda.
 */
class ContentMarkdownSanitizer
{
    /**
     * Etiquetas en línea que se dejan en el Markdown (las de `ContentHtmlSanitizer::inline()`).
     */
    private const ALLOWED = ['b', 'strong', 'i', 'em', 'u', 's', 'br', 'a', 'code', 'mark'];

    public function __construct(
        private readonly ContentFormatConverter $converter,
        private readonly ContentHtmlSanitizer $html,
    ) {}

    /**
     * @param  list<string>  $allowedRaw  HTML de los bloques de HTML libre que ya tiene la página.
     *
     * @throws ValidationException si trae un bloque de HTML libre nuevo o no se puede revisar.
     */
    public function sanitize(string $markdown, array $allowedRaw = []): string
    {
        if (! str_contains($markdown, '<')) {
            return $markdown;
        }

        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);

        /** @var array<int, array{block: AbstractBlock, inlines: list<HtmlInline>}> $groups */
        $groups = [];
        /** @var list<HtmlBlock> $htmlBlocks */
        $htmlBlocks = [];

        $walker = $this->converter->parseMarkdown($markdown)->walker();

        while ($event = $walker->next()) {
            if (! $event->isEntering()) {
                continue;
            }

            $node = $event->getNode();

            if ($node instanceof HtmlBlock) {
                $htmlBlocks[] = $node;
            } elseif ($node instanceof HtmlInline) {
                $block = $this->lineBlock($node);
                $groups[spl_object_id($block)] ??= ['block' => $block, 'inlines' => []];
                $groups[spl_object_id($block)]['inlines'][] = $node;
            }
        }

        /** @var list<array{start: int, end: int, lines: list<string>}> $edits */
        $edits = [];

        foreach ($htmlBlocks as $block) {
            $edit = $this->blockEdit($block, $lines, $allowedRaw);

            if ($edit !== null) {
                $edits[] = $edit;
            }
        }

        foreach ($groups as ['block' => $block, 'inlines' => $inlines]) {
            $edit = $this->inlineEdit($block, $inlines, $lines);

            if ($edit !== null) {
                $edits[] = $edit;
            }
        }

        // De abajo arriba: así los números de línea de lo que queda por
        // cambiar siguen valiendo.
        usort($edits, fn (array $a, array $b): int => $b['start'] <=> $a['start']);

        foreach ($edits as $edit) {
            array_splice($lines, $edit['start'] - 1, $edit['end'] - $edit['start'] + 1, $edit['lines']);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $lines
     * @param  list<string>  $allowedRaw
     * @return array{start: int, end: int, lines: list<string>}|null
     */
    private function blockEdit(HtmlBlock $block, array $lines, array $allowedRaw): ?array
    {
        $literal = $block->getLiteral();
        [$start, $end] = $this->range($block);

        $clean = $this->cleanBlock($literal, $allowedRaw);

        if ($clean === $literal) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
            'lines' => $this->withPrefixes(array_slice($lines, $start - 1, $end - $start + 1), explode("\n", $literal), $clean === '' ? [] : explode("\n", $clean), $start),
        ];
    }

    /**
     * @param  list<string>  $allowedRaw
     */
    private function cleanBlock(string $literal, array $allowedRaw): string
    {
        $wrapped = $this->converter->wrappedBlock($literal);

        if ($wrapped === null || ! $wrapped['intact']) {
            return trim($this->html->inline($literal));
        }

        $block = $wrapped['block'];

        if ($block['type'] === 'raw') {
            $html = $block['data']['html'] ?? null;

            if (is_string($html) && in_array($html, $allowedRaw, true)) {
                return $literal;
            }

            throw ValidationException::withMessages([
                'content' => 'El Markdown trae un bloque de HTML libre que no estaba en la página: sólo un administrador puede añadirlo o cambiarlo.',
            ]);
        }

        $clean = $this->html->block($block);

        return $clean === $block ? $literal : $this->converter->wrapBlock($clean);
    }

    /**
     * @param  list<HtmlInline>  $inlines
     * @param  list<string>  $lines
     * @return array{start: int, end: int, lines: list<string>}|null
     */
    private function inlineEdit(AbstractBlock $block, array $inlines, array $lines): ?array
    {
        [$start, $end] = $this->range($block);
        $text = implode("\n", array_slice($lines, $start - 1, $end - $start + 1));
        $cursor = 0;
        $changed = false;

        foreach ($inlines as $inline) {
            $literal = $inline->getLiteral();
            $position = strpos($text, $literal, $cursor);

            if ($position === false) {
                throw $this->unreadable($start);
            }

            $clean = $this->cleanTag($literal);

            if ($clean !== $literal) {
                $text = substr_replace($text, $clean, $position, strlen($literal));
                $changed = true;
            }

            $cursor = $position + strlen($clean);
        }

        return $changed ? ['start' => $start, 'end' => $end, 'lines' => explode("\n", $text)] : null;
    }

    /**
     * Una etiqueta suelta de HTML en línea: la misma si está permitida y
     * limpia; sin los atributos que no valen; o nada.
     */
    private function cleanTag(string $literal): string
    {
        // Comentarios, <!DOCTYPE>, instrucciones de proceso y CDATA.
        if (preg_match('/^<[!?]/', $literal) === 1) {
            return '';
        }

        if (preg_match('/^<(\/?)([a-zA-Z][a-zA-Z0-9-]*)(.*?)\/?>$/s', $literal, $tag) !== 1) {
            return '';
        }

        $name = strtolower($tag[2]);

        if (! in_array($name, self::ALLOWED, true)) {
            return '';
        }

        if ($tag[1] === '/') {
            return $name === 'br' ? '' : "</{$name}>";
        }

        // Los atributos los decide el mismo limpiador que el de los bloques, con
        // la etiqueta cerrada para que la lea entera. Si la devuelve igual,
        // estaba limpia y se queda como estaba escrita.
        $sample = $name === 'br' ? $literal : $literal."</{$name}>";
        $clean = $this->html->inline($sample);

        if ($clean === $sample) {
            return $literal;
        }

        return preg_match('/^<'.$name.'(?:\s[^>]*)?\/?>/i', $clean, $opening) === 1 ? $opening[0] : '';
    }

    /**
     * Líneas nuevas de un bloque con los prefijos que tenían las originales
     * (`> ` en una cita, la sangría en una lista).
     *
     * @param  list<string>  $source
     * @param  list<string>  $literal
     * @param  list<string>  $replacement
     * @return list<string>
     */
    private function withPrefixes(array $source, array $literal, array $replacement, int $start): array
    {
        $prefixes = [];

        foreach ($source as $index => $line) {
            $content = $literal[$index] ?? '';

            if ($content !== '' && ! str_ends_with($line, $content)) {
                throw $this->unreadable($start + $index);
            }

            $prefixes[] = substr($line, 0, strlen($line) - strlen($content));
        }

        return array_map(
            fn (string $line, int $index): string => ($prefixes[$index] ?? end($prefixes) ?: '').$line,
            $replacement,
            array_keys($replacement),
        );
    }

    /**
     * El bloque con líneas más cercano (una celda de tabla no las tiene; su
     * tabla, sí).
     */
    private function lineBlock(Node $node): AbstractBlock
    {
        for ($parent = $node->parent(); $parent !== null; $parent = $parent->parent()) {
            if ($parent instanceof AbstractBlock && $parent->getStartLine() !== null) {
                return $parent;
            }
        }

        throw $this->unreadable(1);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function range(AbstractBlock $block): array
    {
        $start = (int) $block->getStartLine();

        return [$start, (int) ($block->getEndLine() ?? $start)];
    }

    private function unreadable(int $line): ValidationException
    {
        return ValidationException::withMessages([
            'content' => "No se ha podido revisar el HTML de la línea {$line} del Markdown. Escribe esa etiqueta en una sola línea, sin sangría.",
        ]);
    }
}
