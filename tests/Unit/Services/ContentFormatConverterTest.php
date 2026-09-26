<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ContentPageFormatEnum as Format;
use App\Helpers\TextFormatParseHelper;
use App\Services\Content\ContentFormatConverter;
use Dom\HTMLDocument;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Conversiones entre Editor.js, Markdown y HTML de las páginas de contenido.
 *
 * Lo que se fija aquí es que convertir no estropee páginas: lo que Markdown no
 * sabe expresar va envuelto y vuelve intacto, lo que se toca vuelve como HTML
 * con los cambios, y el HTML que se sirve no cambia por pasar por otro formato.
 */
class ContentFormatConverterTest extends TestCase
{
    private ContentFormatConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new ContentFormatConverter;
    }

    /**
     * Página de Editor.js con bloques que Markdown expresa y otros que no.
     *
     * @return list<array<string, mixed>>
     */
    private function blocks(): array
    {
        return [
            ['id' => 'h1', 'type' => 'header', 'data' => ['text' => 'Sobre el proyecto', 'level' => 2]],
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Mide <b>luz</b> y humedad.'], 'tunes' => ['textVariant' => null]],
            ['id' => 'l1', 'type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['Raspberry Pi', 'Sensor BME280']]],
            ['id' => 'c1', 'type' => 'checklist', 'data' => ['items' => [['text' => 'Temperatura', 'checked' => true], ['text' => 'Rayos', 'checked' => false]]]],
            ['id' => 'q1', 'type' => 'quote', 'data' => ['text' => 'Hazlo simple', 'caption' => 'Alguien', 'alignment' => 'left']],
            ['id' => 'k1', 'type' => 'code', 'data' => ['code' => "echo 1;\necho 2;", 'language' => 'php']],
            ['id' => 'd1', 'type' => 'delimiter', 'data' => []],
            ['id' => 't1', 'type' => 'table', 'data' => ['withHeadings' => true, 'stretched' => false, 'content' => [['Pin', 'Sensor'], ['SDA (14)', 'SDA']]]],
            ['id' => 'a1', 'type' => 'alert', 'data' => ['type' => 'warning', 'align' => 'left', 'message' => 'En desarrollo']],
            ['id' => 'i1', 'type' => 'image', 'data' => [
                'file' => ['url' => 'https://ejemplo.test/foto.webp', 'url_thumbnail' => 'https://ejemplo.test/mini.webp', 'url_large' => 'https://ejemplo.test/grande.webp', 'file_id' => 7],
                'caption' => 'Primer boceto', 'withBorder' => false, 'stretched' => false, 'withBackground' => false,
            ]],
        ];
    }

    private function editorJs(array $blocks): string
    {
        return (string) json_encode(['time' => 1, 'blocks' => $blocks, 'version' => '2.29.0']);
    }

    /**
     * Tipo y datos de cada bloque, sin los ids (que se regeneran).
     *
     * @return list<array{0: string, 1: mixed}>
     */
    private function shape(string $json): array
    {
        return array_map(fn (array $block): array => [$block['type'], $block['data']], $this->converter->decodeBlocks($json));
    }

    #[Test]
    public function the_served_html_of_editorjs_is_the_one_the_helper_already_generated(): void
    {
        $json = $this->editorJs($this->blocks());

        $this->assertSame(TextFormatParseHelper::jsonToHtml($json), $this->converter->toServedHtml($json, Format::EditorJs));
    }

    #[Test]
    public function markdown_gets_real_markdown_for_what_it_can_express(): void
    {
        $markdown = $this->converter->convert($this->editorJs($this->blocks()), Format::EditorJs, Format::Markdown)->content;

        $this->assertStringContainsString('## Sobre el proyecto', $markdown);
        $this->assertStringContainsString('Mide **luz** y humedad.', $markdown);
        $this->assertStringContainsString('- Raspberry Pi', $markdown);
        $this->assertStringContainsString('- [x] Temperatura', $markdown);
        $this->assertStringContainsString("> Hazlo simple\n>\n> — Alguien", $markdown);
        $this->assertStringContainsString("```php\necho 1;\necho 2;\n```", $markdown);
        $this->assertStringContainsString("| Pin | Sensor |\n| --- | --- |\n| SDA (14) | SDA |", $markdown);
    }

    #[Test]
    public function what_markdown_cannot_express_goes_wrapped_and_comes_back_intact(): void
    {
        $blocks = $this->blocks();
        $conversion = $this->converter->convert($this->editorJs($blocks), Format::EditorJs, Format::Markdown);

        // La alerta y la imagen del módulo de ficheros no caben en Markdown.
        $this->assertSame(2, substr_count($conversion->content, ContentFormatConverter::BLOCK_ATTRIBUTE));
        $this->assertStringContainsString('alerta ×1, imagen ×1', implode(' ', $conversion->warnings));

        $back = $this->converter->convert($conversion->content, Format::Markdown, Format::EditorJs);
        $shape = $this->shape($back->content);

        $this->assertSame([$blocks[8]['type'], $blocks[8]['data']], $shape[8]);
        $this->assertSame([$blocks[9]['type'], $blocks[9]['data']], $shape[9]);
        $this->assertSame(array_column($blocks, 'type'), array_column($shape, 0));
    }

    #[Test]
    public function a_round_trip_through_markdown_looks_the_same(): void
    {
        $json = $this->editorJs($this->blocks());

        $markdown = $this->converter->convert($json, Format::EditorJs, Format::Markdown)->content;
        $back = $this->converter->convert($markdown, Format::Markdown, Format::EditorJs)->content;

        $this->assertSame(
            $this->visibleText($this->converter->toServedHtml($json, Format::EditorJs)),
            $this->visibleText($this->converter->toServedHtml($back, Format::EditorJs)),
        );
    }

    #[Test]
    public function a_round_trip_through_html_gives_back_the_exact_blocks(): void
    {
        $json = $this->editorJs($this->blocks());

        $html = $this->converter->convert($json, Format::EditorJs, Format::Html)->content;
        $back = $this->converter->convert($html, Format::Html, Format::EditorJs)->content;

        $this->assertSame($this->shape($json), $this->shape($back));
        // Y la web ve lo mismo que antes: los envoltorios se quitan al servir.
        $this->assertSame(
            $this->visibleText($this->converter->toServedHtml($json, Format::EditorJs)),
            $this->visibleText($this->converter->toServedHtml($html, Format::Html)),
        );
        $this->assertStringNotContainsString(ContentFormatConverter::BLOCK_ATTRIBUTE, $this->converter->toServedHtml($html, Format::Html));
    }

    #[Test]
    public function a_wrapped_block_that_was_edited_comes_back_as_html_with_the_changes(): void
    {
        $markdown = $this->converter->convert($this->editorJs([$this->blocks()[8]]), Format::EditorJs, Format::Markdown)->content;
        $edited = str_replace('En desarrollo', 'Terminado', $markdown);

        $back = $this->converter->convert($edited, Format::Markdown, Format::EditorJs);
        [$block] = $this->converter->decodeBlocks($back->content);

        $this->assertSame('raw', $block['type']);
        $this->assertStringContainsString('Terminado', $block['data']['html']);
        $this->assertStringContainsString('se había cambiado', implode(' ', $back->warnings));
    }

    #[Test]
    public function markdown_is_turned_into_editorjs_blocks(): void
    {
        $markdown = <<<'MD'
        # Título

        Texto con **negrita** y [un enlace](https://ejemplo.test).

        1. Uno
        2. Dos

        - [x] Hecho

        > Cita
        >
        > — Autor

        ```js
        let a = 1;
        ```

        ---

        | A | B |
        | --- | --- |
        | 1 | 2 |

        ![Foto](https://ejemplo.test/foto.png)
        MD;

        $conversion = $this->converter->convert($markdown, Format::Markdown, Format::EditorJs);
        $shape = $this->shape($conversion->content);

        $this->assertSame(
            ['header', 'paragraph', 'list', 'checklist', 'quote', 'code', 'delimiter', 'table', 'image'],
            array_column($shape, 0),
        );
        // El editor sólo tiene títulos del 3 al 6: el h1 y el h2 son de la web.
        $this->assertSame(['text' => 'Título', 'level' => 3], $shape[0][1]);
        $this->assertStringContainsString('1 título de nivel 1 o 2 pasa a nivel 3', implode(' ', $conversion->warnings));
        $this->assertSame('Texto con <strong>negrita</strong> y <a href="https://ejemplo.test">un enlace</a>.', $shape[1][1]['text']);
        $this->assertSame([
            'style' => 'ordered',
            'meta' => ['counterType' => 'numeric'],
            'items' => [
                ['content' => 'Uno', 'meta' => [], 'items' => []],
                ['content' => 'Dos', 'meta' => [], 'items' => []],
            ],
        ], $shape[2][1]);
        $this->assertSame([['text' => 'Hecho', 'checked' => true]], $shape[3][1]['items']);
        $this->assertSame(['text' => 'Cita', 'caption' => 'Autor', 'alignment' => 'left'], $shape[4][1]);
        $this->assertSame(['code' => 'let a = 1;', 'language' => 'js', 'showlinenumbers' => true], $shape[5][1]);
        $this->assertSame([['A', 'B'], ['1', '2']], $shape[7][1]['content']);
        $this->assertSame('https://ejemplo.test/foto.png', $shape[8][1]['file']['url']);
    }

    #[Test]
    public function html_without_an_editorjs_block_goes_as_an_html_block(): void
    {
        $back = $this->converter->convert('<p>Hola</p><section class="x">Algo</section>', Format::Html, Format::EditorJs);
        $shape = $this->shape($back->content);

        $this->assertSame(['paragraph', 'raw'], array_column($shape, 0));
        $this->assertSame('<section class="x">Algo</section>', $shape[1][1]['html']);
        $this->assertStringContainsString('1 trozo de HTML', implode(' ', $back->warnings));
    }

    #[Test]
    public function nested_lists_become_editorjs_lists(): void
    {
        $back = $this->converter->convert("- Uno\n  - Uno bis\n- Dos", Format::Markdown, Format::EditorJs);

        $this->assertSame([['list', [
            'style' => 'unordered',
            'meta' => [],
            'items' => [
                ['content' => 'Uno', 'meta' => [], 'items' => [['content' => 'Uno bis', 'meta' => [], 'items' => []]]],
                ['content' => 'Dos', 'meta' => [], 'items' => []],
            ],
        ]]], $this->shape($back->content));
        $this->assertSame([], $back->warnings);

        // Como lo guarda el editor: `meta` vacío es un objeto, no una lista.
        $this->assertStringContainsString('"meta": {}', $back->content);
        $this->assertStringNotContainsString('"meta": []', $back->content);
    }

    #[Test]
    public function a_list_that_mixes_numbered_and_bulleted_levels_goes_as_html(): void
    {
        $back = $this->converter->convert("1. Uno\n   - Viñeta", Format::Markdown, Format::EditorJs);
        $shape = $this->shape($back->content);

        $this->assertSame(['raw'], array_column($shape, 0));
        $this->assertStringContainsString('Viñeta', $shape[0][1]['html']);
        $this->assertStringContainsString('1 lista mezcla numerada y con viñetas', implode(' ', $back->warnings));
    }

    #[Test]
    public function lists_in_the_new_format_go_to_markdown_with_their_levels(): void
    {
        $markdown = $this->converter->convert($this->editorJs([
            ['id' => 'l1', 'type' => 'list', 'data' => ['style' => 'ordered', 'meta' => ['start' => 3, 'counterType' => 'numeric'], 'items' => [
                ['content' => 'Tres', 'meta' => [], 'items' => [['content' => 'Sub', 'meta' => [], 'items' => []]]],
                ['content' => 'Cuatro', 'meta' => [], 'items' => []],
            ]]],
            ['id' => 'l2', 'type' => 'list', 'data' => ['style' => 'checklist', 'meta' => [], 'items' => [
                ['content' => 'Hecho', 'meta' => ['checked' => true], 'items' => [['content' => 'Falta', 'meta' => ['checked' => false], 'items' => []]]],
            ]]],
        ]), Format::EditorJs, Format::Markdown)->content;

        $this->assertStringContainsString("3. Tres\n   1. Sub\n4. Cuatro", $markdown);
        $this->assertStringContainsString("- [x] Hecho\n  - [ ] Falta", $markdown);
        $this->assertStringNotContainsString(ContentFormatConverter::BLOCK_ATTRIBUTE, $markdown);
    }

    #[Test]
    public function a_list_numbered_with_letters_or_roman_numerals_goes_wrapped_to_markdown(): void
    {
        $block = ['id' => 'l1', 'type' => 'list', 'data' => ['style' => 'ordered', 'meta' => ['counterType' => 'upper-roman'], 'items' => [
            ['content' => 'Uno', 'meta' => [], 'items' => []],
            ['content' => 'Dos', 'meta' => [], 'items' => []],
        ]]];

        $markdown = $this->converter->convert($this->editorJs([$block]), Format::EditorJs, Format::Markdown)->content;

        // Markdown sólo numera con cifras: va envuelto y vuelve tal cual.
        $this->assertStringContainsString(ContentFormatConverter::BLOCK_ATTRIBUTE, $markdown);
        $this->assertStringContainsString('I', $this->converter->toServedHtml($markdown, Format::Markdown));
        $this->assertStringContainsString('II', $this->converter->toServedHtml($markdown, Format::Markdown));

        $back = $this->converter->convert($markdown, Format::Markdown, Format::EditorJs)->content;

        $this->assertSame([['list', $block['data']]], $this->shape($back));
    }

    #[Test]
    public function the_served_html_of_a_nested_list_keeps_its_levels(): void
    {
        $html = $this->converter->toServedHtml($this->editorJs([
            ['id' => 'l1', 'type' => 'list', 'data' => ['style' => 'ordered', 'meta' => ['start' => 2, 'counterType' => 'lower-alpha'], 'items' => [
                ['content' => 'Padre', 'meta' => [], 'items' => [['content' => 'Hijo', 'meta' => [], 'items' => []]]],
            ]]],
            ['id' => 'l2', 'type' => 'list', 'data' => ['style' => 'checklist', 'meta' => [], 'items' => [
                ['content' => 'Tarea', 'meta' => ['checked' => true], 'items' => [['content' => 'Subtarea', 'meta' => ['checked' => false], 'items' => []]]],
            ]]],
        ]), Format::EditorJs);

        $body = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$html.'</body>', LIBXML_NOERROR)->body;

        // La sublista va dentro del contenido del elemento padre.
        $child = $body->querySelector('#l1 .r-list-item-content .r-list-box .r-list-item-content');
        $this->assertSame('Hijo', trim((string) $child?->textContent));

        // Numeración con letras, empezando donde se dijo; la sublista, desde el principio.
        $icons = array_map(fn ($icon): string => trim($icon->textContent), iterator_to_array($body->querySelectorAll('#l1 .r-list-item-icon')));
        $this->assertSame(['b', 'a'], $icons);

        // Una lista de casillas se pinta como el bloque `checklist`.
        $this->assertSame('Subtarea', trim((string) $body->querySelector('#l2.r-checkbox-container .r-checkbox-item-content .r-checkbox-item-content')?->textContent));
    }

    #[Test]
    public function html_and_markdown_keep_their_h1_and_h2(): void
    {
        $markdown = $this->converter->convert('<h2>Sección</h2><p>Texto</p>', Format::Html, Format::Markdown);

        $this->assertStringContainsString('## Sección', $markdown->content);
        $this->assertStringNotContainsString('nivel 3', implode(' ', $markdown->warnings));
    }

    #[Test]
    public function a_video_inside_markdown_is_not_escaped(): void
    {
        // El conversor de GitHub de `Str::markdown()` escapa los <iframe>.
        $markdown = $this->converter->convert(
            $this->editorJs([['id' => 'e1', 'type' => 'embed', 'data' => [
                'service' => 'youtube', 'source' => 'https://youtu.be/x', 'embed' => 'https://www.youtube.com/embed/x',
                'width' => 580, 'height' => 320, 'caption' => 'Vídeo',
            ]]]),
            Format::EditorJs,
            Format::Markdown,
        )->content;

        $served = $this->converter->toServedHtml($markdown, Format::Markdown);

        $this->assertStringContainsString('<iframe', $served);
        $this->assertStringNotContainsString('&lt;iframe', $served);
        $this->assertStringNotContainsString(ContentFormatConverter::BLOCK_ATTRIBUTE, $served);
    }

    #[Test]
    public function markdown_says_what_the_web_already_shows(): void
    {
        // La v1 quita los saltos de línea del texto al generar el HTML, así que
        // la web muestra «ahorrarmesubir». El Markdown no puede enseñar otra cosa.
        $json = $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => "ahorrarme\nsubir"]]]);

        $markdown = $this->converter->convert($json, Format::EditorJs, Format::Markdown)->content;

        $this->assertSame("ahorrarmesubir\n", $markdown);
    }

    #[Test]
    public function an_image_uploaded_from_the_v2_editor_renders_without_thumbnails(): void
    {
        // Las subidas del editor de la v2 sólo traen `url`; la vista pedía
        // `url_thumbnail` y reventaba al generar el HTML.
        $json = $this->editorJs([['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => 'https://ejemplo.test/a.webp', 'file_id' => 3], 'caption' => '']]]);

        $this->assertStringContainsString('https://ejemplo.test/a.webp', $this->converter->toServedHtml($json, Format::EditorJs));
    }

    #[Test]
    public function something_that_is_not_editorjs_json_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->converter->convert('{"no": "bloques"}', Format::EditorJs, Format::Markdown);
    }

    private function visibleText(string $html): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))));
    }
}
