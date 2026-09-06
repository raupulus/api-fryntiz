<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Format\Cifra;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Formato de las cifras de las vistas públicas: punto de millar y sin
 * decimales.
 */
class CifraTest extends TestCase
{
    #[Test]
    public function la_cifra_entera_usa_punto_de_millar_y_no_lleva_decimales(): void
    {
        $this->assertSame('1.235', Cifra::entera(1234.56));
        $this->assertSame('75.884.812', Cifra::entera(75884812));
        $this->assertSame('2', Cifra::entera(2.0));
        $this->assertSame('0', Cifra::entera(0.4));
        $this->assertSame('0', Cifra::entera(null));
    }

    #[Test]
    public function las_cifras_grandes_se_redondean_a_millares(): void
    {
        // El caso de la portada: 75.884.812 pulsaciones acumuladas.
        $this->assertSame('75.885', Cifra::miles(75884812));
        $this->assertSame('24.124', Cifra::miles(24123936));
        $this->assertSame('68', Cifra::miles(67673));

        // Las sumas de PostgreSQL llegan como cadena numérica.
        $this->assertSame('75.885', Cifra::miles('75884812'));
    }

    #[Test]
    public function por_debajo_del_millar_se_muestra_la_cifra_tal_cual(): void
    {
        // Redondear a millares dejaría un «0» en la tarjeta.
        $this->assertSame('812', Cifra::miles(812));
        $this->assertSame('999', Cifra::miles(999));
        $this->assertSame('1', Cifra::miles(1000));
        $this->assertSame('0', Cifra::miles(null));
    }
}
