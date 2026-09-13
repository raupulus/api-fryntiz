<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Format\Figures;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Formato de las cifras de las vistas públicas: punto de millar y sin
 * decimales.
 */
class FiguresTest extends TestCase
{
    #[Test]
    public function as_integer_uses_thousands_separator_and_no_decimals(): void
    {
        $this->assertSame('1.235', Figures::asInteger(1234.56));
        $this->assertSame('75.884.812', Figures::asInteger(75884812));
        $this->assertSame('2', Figures::asInteger(2.0));
        $this->assertSame('0', Figures::asInteger(0.4));
        $this->assertSame('0', Figures::asInteger(null));
    }

    #[Test]
    public function figures_at_a_million_or_above_get_an_explicit_scale_suffix(): void
    {
        // El caso de la portada: 75.884.812 pulsaciones acumuladas.
        $this->assertSame('75,88 M', Figures::abbreviated(75884812));
        $this->assertSame('24,12 M', Figures::abbreviated(24123936));

        // Las sumas de PostgreSQL llegan como cadena numérica.
        $this->assertSame('75,88 M', Figures::abbreviated('75884812'));
    }

    #[Test]
    public function below_a_million_the_figure_is_shown_in_full(): void
    {
        // Sin sufijo de escala no puede haber ambigüedad: la cifra es la real.
        $this->assertSame('67.673', Figures::abbreviated(67673));
        $this->assertSame('812', Figures::abbreviated(812));
        $this->assertSame('999.999', Figures::abbreviated(999999));
        $this->assertSame('0', Figures::abbreviated(null));
    }
}
