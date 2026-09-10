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
    public function large_figures_are_rounded_to_thousands(): void
    {
        // El caso de la portada: 75.884.812 pulsaciones acumuladas.
        $this->assertSame('75.885', Figures::abbreviated(75884812));
        $this->assertSame('24.124', Figures::abbreviated(24123936));
        $this->assertSame('68', Figures::abbreviated(67673));

        // Las sumas de PostgreSQL llegan como cadena numérica.
        $this->assertSame('75.885', Figures::abbreviated('75884812'));
    }

    #[Test]
    public function below_a_thousand_the_figure_is_shown_as_is(): void
    {
        // Redondear a millares dejaría un «0» en la tarjeta.
        $this->assertSame('812', Figures::abbreviated(812));
        $this->assertSame('999', Figures::abbreviated(999));
        $this->assertSame('1', Figures::abbreviated(1000));
        $this->assertSame('0', Figures::abbreviated(null));
    }
}
