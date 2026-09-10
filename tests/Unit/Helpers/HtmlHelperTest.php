<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\HtmlHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El HTML que se escribe desde la intranet, servido sin abrir la puerta.
 *
 * Las descripciones salían con `{{ }}`, así que un `<p>` se veía literalmente
 * como «&lt;p&gt;». La solución fácil —`{!! !!}` a secas— convierte cualquier
 * campo de texto del panel en un XSS almacenado.
 */
class HtmlHelperTest extends TestCase
{
    #[Test]
    public function allows_basic_formatting_through(): void
    {
        $html = '<p>Un <strong>bonsái</strong> con <em>riego</em>.<br/>Segunda línea.</p>';

        // El saneador normaliza `<br/>` a `<br />`, que es lo mismo.
        $this->assertSame(
            '<p>Un <strong>bonsái</strong> con <em>riego</em>.<br />Segunda línea.</p>',
            (string) HtmlHelper::safeBasic($html)
        );
    }

    #[Test]
    public function allows_lists_and_divs(): void
    {
        $clean = (string) HtmlHelper::safeBasic('<div><ul><li>Uno</li><li>Dos</li></ul></div>');

        $this->assertStringContainsString('<ul>', $clean);
        $this->assertStringContainsString('<li>Uno</li>', $clean);
        $this->assertStringContainsString('<div>', $clean);
    }

    #[Test]
    public function strips_scripts(): void
    {
        $clean = (string) HtmlHelper::safeBasic('<p>Hola</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert(1)', $clean);
        $this->assertStringContainsString('<p>Hola</p>', $clean);
    }

    /**
     * Lo que `strip_tags()` con lista blanca **no** habría parado: la etiqueta
     * está permitida y el veneno va en el atributo.
     */
    #[Test]
    public function strips_event_attributes(): void
    {
        $clean = (string) HtmlHelper::safeBasic('<p onclick="alert(1)">Hola</p>');

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('Hola', $clean);
    }

    #[Test]
    public function a_javascript_link_does_not_pass(): void
    {
        $clean = (string) HtmlHelper::safeBasic('<a href="javascript:alert(1)">Pulsa</a>');

        $this->assertStringNotContainsString('javascript:', $clean);
    }

    #[Test]
    public function a_normal_link_passes_and_gets_a_rel_attribute(): void
    {
        $clean = (string) HtmlHelper::safeBasic('<a href="https://raupulus.dev">Web</a>');

        $this->assertStringContainsString('href="https://raupulus.dev"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    #[Test]
    public function does_not_allow_images_or_iframes(): void
    {
        $clean = (string) HtmlHelper::safeBasic(
            '<p>Texto</p><img src="x" onerror="alert(1)"><iframe src="https://ejemplo.test"></iframe>'
        );

        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringContainsString('Texto', $clean);
    }

    #[Test]
    public function an_empty_text_does_not_crash(): void
    {
        $this->assertSame('', (string) HtmlHelper::safeBasic(null));
        $this->assertSame('', (string) HtmlHelper::safeBasic(''));
        $this->assertSame('', (string) HtmlHelper::safeBasic('   '));
    }

    /**
     * Un texto sin ninguna etiqueta —que es lo que hay hoy en la base de
     * datos— tiene que salir igual que entró.
     */
    #[Test]
    public function plain_text_still_comes_out_unchanged(): void
    {
        $text = 'Bonsái de olmo chino regado con sensor de humedad.';

        $this->assertSame($text, (string) HtmlHelper::safeBasic($text));
    }

    #[Test]
    public function the_meta_description_is_plain_and_trimmed(): void
    {
        $this->assertSame(
            'Un bonsái con riego.',
            HtmlHelper::toMetaDescription('<p>Un <strong>bonsái</strong> con riego.</p>')
        );

        $long = HtmlHelper::toMetaDescription('<p>'.str_repeat('palabra ', 60).'</p>', 50);

        $this->assertLessThanOrEqual(50, mb_strlen($long));
        $this->assertStringEndsWith('…', $long);
        $this->assertStringNotContainsString('<', $long);
    }
}
