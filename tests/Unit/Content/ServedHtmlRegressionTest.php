<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Enums\ContentPageFormatEnum;
use App\Services\Content\ContentFormatConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El HTML que se sirve de las páginas reales no puede cambiar sin querer.
 *
 * `tests/Fixtures/content-pages/` guarda, de cada página de producción con
 * Editor.js (volcado del 2026-09-24), su JSON y el HTML que se servía ese día.
 * Regenerar el HTML desde el JSON tiene que dar exactamente lo mismo: es lo que
 * ven las webs.
 *
 * Si un cambio altera el HTML a propósito (por ejemplo, una plantilla de
 * bloque que deja de pintar un «—» suelto), la diferencia se enseña y se
 * aprueba antes de tocar el fixture. No se regenera el fixture para que el
 * test vuelva a verde.
 */
class ServedHtmlRegressionTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../../Fixtures/content-pages';

    /**
     * @return array<string, array{0: string}>
     */
    public static function pages(): array
    {
        $pages = [];

        foreach (glob(self::FIXTURES.'/*.json') ?: [] as $json) {
            $id = basename($json, '.json');
            $pages["página {$id}"] = [$id];
        }

        return $pages;
    }

    #[Test]
    public function there_are_fixtures_for_the_real_pages(): void
    {
        $this->assertCount(19, self::pages(), 'Faltan fixtures de páginas reales en tests/Fixtures/content-pages.');
    }

    #[Test]
    #[DataProvider('pages')]
    public function the_served_html_of_a_real_page_does_not_change(string $id): void
    {
        $json = (string) file_get_contents(self::FIXTURES."/{$id}.json");
        $expected = (string) file_get_contents(self::FIXTURES."/{$id}.html");

        $served = app(ContentFormatConverter::class)->toServedHtml($json, ContentPageFormatEnum::EditorJs);

        $this->assertSame(
            $this->normalize($expected),
            $this->normalize($served),
            "El HTML servido de la página {$id} ha cambiado respecto al de producción.",
        );
    }

    /**
     * `@editorjs/list` 2.x reescribe las listas al abrir la página, y al
     * guardarla quedan en su formato: `{style, meta, items: [{content, meta,
     * items}]}`. La web tiene que seguir viendo lo mismo.
     */
    #[Test]
    #[DataProvider('pages')]
    public function lists_upgraded_to_the_new_format_serve_the_same_html(string $id): void
    {
        $page = json_decode((string) file_get_contents(self::FIXTURES."/{$id}.json"), true);
        $expected = (string) file_get_contents(self::FIXTURES."/{$id}.html");

        $page['blocks'] = array_map(function (array $block): array {
            if ($block['type'] !== 'list') {
                return $block;
            }

            $block['data'] = [
                'style' => $block['data']['style'],
                'meta' => $block['data']['style'] === 'ordered' ? ['counterType' => 'numeric'] : [],
                'items' => array_map(
                    fn (string $item): array => ['content' => $item, 'meta' => [], 'items' => []],
                    $block['data']['items'],
                ),
            ];

            return $block;
        }, $page['blocks']);

        $served = app(ContentFormatConverter::class)->toServedHtml((string) json_encode($page), ContentPageFormatEnum::EditorJs);

        $this->assertSame($this->normalize($expected), $this->normalize($served));
    }

    /**
     * Las mismas páginas abiertas en el editor del panel (Editor.js 2.31.7) y
     * regrabadas con `editor.save()` sin tocar nada, el 2026-09-24. Cambian
     * las listas (formato nuevo, sin el `<br>` final de cada elemento), el pie
     * de foto vacío (`null` → `""`), `textVariant` (`null` → `""`) y los datos
     * del separador (`[]` → `{}`). En la web no puede cambiar nada.
     */
    #[Test]
    #[DataProvider('pages')]
    public function pages_resaved_by_the_current_editor_serve_the_same_html(string $id): void
    {
        $json = (string) file_get_contents(self::FIXTURES."/editorjs-2.31.7/{$id}.json");
        $expected = (string) file_get_contents(self::FIXTURES."/{$id}.html");

        $this->assertSame('2.31.7', json_decode($json, true)['version']);

        $served = app(ContentFormatConverter::class)->toServedHtml($json, ContentPageFormatEnum::EditorJs);

        $this->assertSame($this->normalize($expected), $this->normalize($served));
    }

    /**
     * Sólo se ignoran las diferencias de espacios, que no cambian lo que se ve.
     */
    private function normalize(string $html): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $html));
    }
}
