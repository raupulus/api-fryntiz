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
    public function deja_pasar_el_formato_basico(): void
    {
        $html = '<p>Un <strong>bonsái</strong> con <em>riego</em>.<br/>Segunda línea.</p>';

        // El saneador normaliza `<br/>` a `<br />`, que es lo mismo.
        $this->assertSame(
            '<p>Un <strong>bonsái</strong> con <em>riego</em>.<br />Segunda línea.</p>',
            (string) HtmlHelper::safeBasic($html)
        );
    }

    #[Test]
    public function admite_listas_y_divs(): void
    {
        $limpio = (string) HtmlHelper::safeBasic('<div><ul><li>Uno</li><li>Dos</li></ul></div>');

        $this->assertStringContainsString('<ul>', $limpio);
        $this->assertStringContainsString('<li>Uno</li>', $limpio);
        $this->assertStringContainsString('<div>', $limpio);
    }

    #[Test]
    public function se_lleva_los_scripts(): void
    {
        $limpio = (string) HtmlHelper::safeBasic('<p>Hola</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $limpio);
        $this->assertStringNotContainsString('alert(1)', $limpio);
        $this->assertStringContainsString('<p>Hola</p>', $limpio);
    }

    /**
     * Lo que `strip_tags()` con lista blanca **no** habría parado: la etiqueta
     * está permitida y el veneno va en el atributo.
     */
    #[Test]
    public function se_lleva_los_atributos_de_evento(): void
    {
        $limpio = (string) HtmlHelper::safeBasic('<p onclick="alert(1)">Hola</p>');

        $this->assertStringNotContainsString('onclick', $limpio);
        $this->assertStringContainsString('Hola', $limpio);
    }

    #[Test]
    public function un_enlace_con_javascript_no_pasa(): void
    {
        $limpio = (string) HtmlHelper::safeBasic('<a href="javascript:alert(1)">Pulsa</a>');

        $this->assertStringNotContainsString('javascript:', $limpio);
    }

    #[Test]
    public function un_enlace_normal_si_pasa_y_sale_con_rel(): void
    {
        $limpio = (string) HtmlHelper::safeBasic('<a href="https://raupulus.dev">Web</a>');

        $this->assertStringContainsString('href="https://raupulus.dev"', $limpio);
        $this->assertStringContainsString('rel="noopener noreferrer"', $limpio);
    }

    #[Test]
    public function no_admite_imagenes_ni_iframes(): void
    {
        $limpio = (string) HtmlHelper::safeBasic(
            '<p>Texto</p><img src="x" onerror="alert(1)"><iframe src="https://ejemplo.test"></iframe>'
        );

        $this->assertStringNotContainsString('<img', $limpio);
        $this->assertStringNotContainsString('<iframe', $limpio);
        $this->assertStringContainsString('Texto', $limpio);
    }

    #[Test]
    public function un_texto_vacio_no_revienta(): void
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
    public function el_texto_plano_de_siempre_sigue_saliendo_igual(): void
    {
        $texto = 'Bonsái de olmo chino regado con sensor de humedad.';

        $this->assertSame($texto, (string) HtmlHelper::safeBasic($texto));
    }

    #[Test]
    public function la_meta_description_va_en_plano_y_recortada(): void
    {
        $this->assertSame(
            'Un bonsái con riego.',
            HtmlHelper::toMetaDescription('<p>Un <strong>bonsái</strong> con riego.</p>')
        );

        $largo = HtmlHelper::toMetaDescription('<p>'.str_repeat('palabra ', 60).'</p>', 50);

        $this->assertLessThanOrEqual(50, mb_strlen($largo));
        $this->assertStringEndsWith('…', $largo);
        $this->assertStringNotContainsString('<', $largo);
    }
}
