<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Helpers\TextFormatParseHelper;
use Dom\HTMLDocument;
use Dom\HTMLElement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las plantillas de bloque aguantan datos incompletos y pintan lo que hay
 * (A2 de la auditoría de contenidos del 2026-09-24).
 *
 * Antes daban por hecho que cada bloque traía todos sus datos: si faltaba uno,
 * generar el HTML reventaba y la página no se podía guardar. Pasaba con los
 * adjuntos subidos desde el editor de la v2, con las tarjetas de enlace de
 * webs que no se dejan leer y con el JSON pegado a mano.
 */
class TolerantBlockTemplatesTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $type, array $data): HTMLElement
    {
        $html = TextFormatParseHelper::arrayToHtml([['id' => 'b1', 'type' => $type, 'data' => $data]]);

        return HTMLDocument::createFromString('<!DOCTYPE html><body>'.$html.'</body>', LIBXML_NOERROR)->body;
    }

    #[Test]
    public function a_link_card_without_metadata_is_a_plain_link(): void
    {
        $body = $this->render('linkTool', ['link' => 'https://ejemplo.test/articulo', 'meta' => []]);

        $this->assertNull($body->querySelector('.r-web-preview-container'));
        $link = $body->querySelector('a');
        $this->assertSame('https://ejemplo.test/articulo', $link?->getAttribute('href'));
        $this->assertSame('ejemplo.test/articulo', trim((string) $link?->textContent));
    }

    #[Test]
    public function a_link_card_without_meta_at_all_is_a_plain_link_too(): void
    {
        $body = $this->render('linkTool', ['link' => 'https://ejemplo.test/']);

        $this->assertSame('https://ejemplo.test/', $body->querySelector('a')?->getAttribute('href'));
    }

    #[Test]
    public function a_link_card_without_title_uses_the_address_as_title(): void
    {
        $body = $this->render('linkTool', ['link' => 'https://ejemplo.test/a', 'meta' => ['image' => ['url' => 'https://ejemplo.test/i.png']]]);

        $this->assertSame('ejemplo.test/a', trim((string) $body->querySelector('.r-web-preview-title')?->textContent));
    }

    /**
     * La forma exacta que devuelve hoy `EditorJsController::upload()`.
     */
    #[Test]
    public function an_attachment_uploaded_from_the_v2_editor_shows_its_name_and_download(): void
    {
        $body = $this->render('attaches', [
            'file' => ['url' => 'https://api.test/file/get/9/informe.pdf', 'name' => 'informe.pdf', 'size' => 2048, 'extension' => 'pdf', 'file_id' => 9],
            'title' => '',
        ]);

        $this->assertStringContainsString('informe.pdf', (string) $body->querySelector('.r-attaches-info')?->textContent);
        $this->assertSame('https://api.test/file/get/9/informe.pdf', $body->querySelector('.r-attaches-download-link')?->getAttribute('href'));
        $this->assertSame('9', $body->querySelector('.r-attaches-container')?->getAttribute('data-file_id'));
        $this->assertNull($body->querySelector('.r-attaches-img'));
    }

    #[Test]
    public function an_attachment_without_anything_does_not_break(): void
    {
        $body = $this->render('attaches', []);

        $this->assertNotNull($body->querySelector('.r-attaches-container'));
    }

    #[Test]
    public function a_quote_without_author_has_no_author_line(): void
    {
        $body = $this->render('quote', ['text' => 'Hazlo simple']);

        $this->assertNull($body->querySelector('.r-blockquote-caption'));
        $this->assertStringNotContainsString('—', $body->textContent);
        $this->assertStringContainsString('Hazlo simple', $body->textContent);
    }

    #[Test]
    public function an_image_without_caption_has_no_caption(): void
    {
        $body = $this->render('image', ['file' => ['url' => 'https://ejemplo.test/a.webp']]);

        $this->assertNull($body->querySelector('figcaption'));
        $this->assertSame('https://ejemplo.test/a.webp', $body->querySelector('img')?->getAttribute('src'));
    }

    #[Test]
    public function a_list_without_style_is_bulleted(): void
    {
        $body = $this->render('list', ['items' => ['Uno', 'Dos']]);

        $this->assertCount(2, $body->querySelectorAll('.r-list-item-icon svg'));
    }

    #[Test]
    public function an_alert_without_type_or_alignment_is_an_informative_one_on_the_left(): void
    {
        $body = $this->render('alert', ['message' => 'Cuidado']);

        $classes = (string) $body->querySelector('.r-alert-container')?->getAttribute('class');
        $this->assertStringContainsString('r-alert-type-info', $classes);
        $this->assertStringContainsString('r-alert-align-left', $classes);
    }

    #[Test]
    public function a_table_without_heading_flag_has_no_header(): void
    {
        $body = $this->render('table', ['content' => [['A', 'B'], ['1', '2']]]);

        $this->assertNull($body->querySelector('thead'));
        $this->assertCount(2, $body->querySelectorAll('tbody tr'));
    }

    #[Test]
    public function a_table_without_rows_does_not_break(): void
    {
        $this->assertNotNull($this->render('table', ['withHeadings' => true])->querySelector('table'));
    }

    #[Test]
    public function a_video_without_caption_or_size_uses_the_default_size(): void
    {
        $body = $this->render('embed', ['service' => 'youtube', 'embed' => 'https://www.youtube.com/embed/x']);

        $this->assertNull($body->querySelector('.r-embed-title'));
        $iframe = $body->querySelector('iframe');
        $this->assertSame('580', $iframe?->getAttribute('data-width'));
        $this->assertSame('320', $iframe?->getAttribute('data-height'));
    }

    #[Test]
    public function a_warning_without_title_shows_only_the_message(): void
    {
        $body = $this->render('warning', ['message' => 'Sólo esto']);

        $this->assertNull($body->querySelector('.r-warning-title'));
        $this->assertStringContainsString('Sólo esto', (string) $body->querySelector('.r-warning-body')?->textContent);
    }

    #[Test]
    public function code_is_shown_as_text_not_as_html(): void
    {
        $body = $this->render('code', ['code' => "<div class=\"x\">hola</div>\n<script>alert(1)</script>"]);

        $this->assertNull($body->querySelector('code div'));
        $this->assertNull($body->querySelector('script'));
        $this->assertStringContainsString('<div class="x">hola</div>', (string) $body->querySelector('code')?->textContent);
    }

    #[Test]
    public function blocks_without_data_or_id_do_not_break_and_unknown_types_are_skipped(): void
    {
        $html = TextFormatParseHelper::arrayToHtml([
            ['type' => 'header'],
            ['type' => 'paragraph', 'data' => null],
            ['type' => 'herramienta-inventada', 'data' => ['x' => 1]],
            ['sin' => 'tipo'],
            'no es un bloque',
        ]);

        $body = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$html.'</body>', LIBXML_NOERROR)->body;

        $this->assertNotNull($body->querySelector('h3'));
        $this->assertNotNull($body->querySelector('p.r-paragraph'));
        $this->assertStringNotContainsString('inventada', $html);
    }
}
