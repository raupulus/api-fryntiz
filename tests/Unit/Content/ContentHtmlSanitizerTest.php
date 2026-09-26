<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Enums\ContentPageFormatEnum;
use App\Services\Content\ContentFormatConverter;
use App\Services\Content\ContentHtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Limpieza del HTML de los bloques al guardar (B1 de la auditoría de
 * contenidos del 2026-09-24): fuera todo lo que pueda ejecutar código, dentro
 * el formato que produce el editor.
 */
class ContentHtmlSanitizerTest extends TestCase
{
    private ContentHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new ContentHtmlSanitizer;
    }

    /**
     * Ataques típicos de la hoja de trucos de XSS de OWASP.
     *
     * @return array<string, array{string}>
     */
    public static function attacks(): array
    {
        return [
            'img onerror' => ['Hola <img src=x onerror="alert(1)"> mundo'],
            'script' => ['Hola <script>alert(1)</script> mundo'],
            'enlace javascript' => ['<a href="javascript:alert(1)">pulsa</a>'],
            'enlace javascript con mayúsculas y tabulador' => ["<a href=\"JaVa\tScRiPt:alert(1)\">pulsa</a>"],
            'enlace data' => ['<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">pulsa</a>'],
            'svg onload' => ['<svg onload="alert(1)"><circle r="1"/></svg>texto'],
            'iframe' => ['<iframe src="https://malo.test"></iframe>texto'],
            'style con expression' => ['<b style="width: expression(alert(1))">negrita</b>'],
            'onmouseover en negrita' => ['<b onmouseover="alert(1)">negrita</b>'],
            'body onload' => ['<body onload="alert(1)">texto</body>'],
            'object' => ['<object data="javascript:alert(1)"></object>texto'],
            'details ontoggle' => ['<details open ontoggle="alert(1)">texto</details>'],
            'etiqueta rota' => ['<img """><script>alert(1)</script>">'],
            'comentario condicional' => ['<!--[if gte IE 4]><script>alert(1)</script><![endif]-->texto'],
            'mark con clase y evento' => ['<mark class="cdx-marker x" onclick="alert(1)">ojo</mark>'],
        ];
    }

    #[Test]
    #[DataProvider('attacks')]
    public function nothing_that_runs_code_survives(string $html): void
    {
        $clean = strtolower($this->sanitizer->inline($html));

        foreach (['<script', '<img', '<svg', '<iframe', '<object', 'onerror', 'onload', 'onclick', 'onmouseover', 'ontoggle', 'javascript:', 'data:', 'expression', 'style='] as $needle) {
            $this->assertStringNotContainsString($needle, $clean, "Queda «{$needle}» en: {$clean}");
        }
    }

    #[Test]
    public function the_editor_formatting_is_kept_untouched(): void
    {
        $html = 'Texto con <b>negrita</b>, <i>cursiva</i>, <u>subrayado</u>, <s>tachado</s>,<br>'
            .'<a href="https://ejemplo.test" target="_blank" rel="noopener">un enlace</a>, '
            .'<a href="mailto:hola@ejemplo.test">correo</a>, <code class="inline-code">$x</code> y '
            .'<mark class="cdx-marker">resaltado</mark>&nbsp;fin.';

        $this->assertSame($html, $this->sanitizer->inline($html));
    }

    #[Test]
    public function unknown_tags_go_away_but_their_text_stays(): void
    {
        $this->assertSame('Hola mundo', $this->sanitizer->inline('<span style="color:red">Hola</span> <font>mundo</font>'));
    }

    #[Test]
    public function the_alert_keeps_its_divs(): void
    {
        $html = '<div>Primera línea</div><div>Segunda<br></div>';

        $this->assertSame($html, $this->sanitizer->alertMessage($html));
        $this->assertStringNotContainsString('onclick', $this->sanitizer->alertMessage('<div onclick="x()">a</div>'));
    }

    #[Test]
    public function a_link_card_keeps_only_text(): void
    {
        $this->assertSame('Título', $this->sanitizer->text('<b>Título</b><script>x()</script>'));
    }

    #[Test]
    public function every_text_field_of_every_block_type_is_cleaned(): void
    {
        $bad = '<img src=x onerror=alert(1)>ok';

        $blocks = $this->sanitizer->blocks([
            ['type' => 'paragraph', 'data' => ['text' => $bad]],
            ['type' => 'header', 'data' => ['text' => $bad, 'level' => 3]],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => [$bad]]],
            ['type' => 'list', 'data' => ['style' => 'ordered', 'meta' => [], 'items' => [['content' => $bad, 'meta' => [], 'items' => [['content' => $bad, 'meta' => [], 'items' => []]]]]]],
            ['type' => 'checklist', 'data' => ['items' => [['text' => $bad, 'checked' => true]]]],
            ['type' => 'quote', 'data' => ['text' => $bad, 'caption' => $bad, 'alignment' => 'left']],
            ['type' => 'table', 'data' => ['content' => [[$bad, $bad]]]],
            ['type' => 'alert', 'data' => ['type' => 'info', 'message' => $bad]],
            ['type' => 'warning', 'data' => ['title' => $bad, 'message' => $bad]],
            ['type' => 'image', 'data' => ['file' => ['url' => 'https://x.test/a.webp'], 'caption' => $bad]],
            ['type' => 'embed', 'data' => ['embed' => 'https://www.youtube.com/embed/x', 'caption' => $bad]],
            ['type' => 'attaches', 'data' => ['file' => ['url' => 'https://x.test/a.pdf'], 'title' => $bad]],
            ['type' => 'linkTool', 'data' => ['link' => 'https://x.test', 'meta' => ['title' => $bad, 'description' => $bad]]],
        ]);

        $json = (string) json_encode($blocks);
        $this->assertStringNotContainsString('onerror', $json);
        $this->assertStringNotContainsString('<img', $json);
        $this->assertSame('ok', $blocks[0]['data']['text']);
        $this->assertSame('ok', $blocks[3]['data']['items'][0]['items'][0]['content']);
        $this->assertSame('ok', $blocks[12]['data']['meta']['description']);
    }

    #[Test]
    public function raw_html_and_code_are_not_touched(): void
    {
        $blocks = [
            ['type' => 'raw', 'data' => ['html' => '<section onclick="x()">libre</section>']],
            ['type' => 'code', 'data' => ['code' => '<script>codigo()</script>']],
        ];

        $this->assertSame($blocks, $this->sanitizer->blocks($blocks));
    }

    #[Test]
    public function the_19_real_pages_do_not_change_at_all(): void
    {
        foreach (glob(__DIR__.'/../../Fixtures/content-pages/*.json') ?: [] as $file) {
            $blocks = json_decode((string) file_get_contents($file), true)['blocks'];

            $this->assertSame($blocks, $this->sanitizer->blocks($blocks), basename($file));
        }
    }

    #[Test]
    public function the_pages_resaved_by_the_current_editor_do_not_change_either(): void
    {
        $converter = new ContentFormatConverter;

        foreach (glob(__DIR__.'/../../Fixtures/content-pages/editorjs-2.31.7/*.json') ?: [] as $file) {
            $json = (string) file_get_contents($file);
            $blocks = $converter->decodeBlocks($json);

            $this->assertSame($blocks, $this->sanitizer->blocks($blocks), basename($file));
            $this->assertNotSame('', $converter->toServedHtml($json, ContentPageFormatEnum::EditorJs));
        }
    }
}
